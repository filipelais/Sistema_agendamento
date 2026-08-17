<?php
/**
 * Endpoint: /api/pacientes.php
 *
 * GET  ?busca=termo   Lista pacientes, com filtro opcional por nome ou CPF
 * GET  ?id=5          Retorna um paciente específico
 * POST                Cadastra um novo paciente
 */

require_once __DIR__ . '/bootstrap.php';

exigirAutenticacao();

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    // --- Consulta de um paciente específico ---
    if (isset($_GET['id'])) {
        $id = (int) $_GET['id'];

        $stmt = $pdo->prepare(
            "SELECT id, nome, cpf, data_nascimento, telefone, email FROM pacientes WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $paciente = $stmt->fetch();

        if (!$paciente) {
            erro('Paciente não encontrado.', 404);
        }

        $paciente['cpf_formatado'] = formatarCpf($paciente['cpf']);

        responder(['dados' => $paciente]);
    }

    // --- Listagem com busca ---
    $busca = trim($_GET['busca'] ?? '');
    $limite = min(50, max(1, (int) ($_GET['limite'] ?? 20)));

    $where = '';
    $parametros = [];

    if ($busca !== '') {
        $where = "WHERE nome LIKE :busca OR cpf LIKE :buscaCpf";
        $parametros[':busca'] = '%' . $busca . '%';
        $parametros[':buscaCpf'] = '%' . apenasNumeros($busca) . '%';
    }

    $sql = "SELECT id, nome, cpf, data_nascimento, telefone, email
            FROM pacientes
            {$where}
            ORDER BY nome ASC
            LIMIT :limite";

    $stmt = $pdo->prepare($sql);

    foreach ($parametros as $chave => $valor) {
        $stmt->bindValue($chave, $valor);
    }

    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();

    $pacientes = $stmt->fetchAll();

    // Acrescenta o CPF formatado para facilitar a exibição no cliente
    foreach ($pacientes as &$paciente) {
        $paciente['cpf_formatado'] = formatarCpf($paciente['cpf']);
    }
    unset($paciente);

    responder([
        'total' => count($pacientes),
        'dados' => $pacientes
    ]);
}

if ($metodo === 'POST') {
    $entrada = corpoJson();

    $nome = trim($entrada['nome'] ?? '');
    $cpf = apenasNumeros($entrada['cpf'] ?? '');
    $dataNascimento = $entrada['data_nascimento'] ?? '';
    $telefone = trim($entrada['telefone'] ?? '');
    $email = trim($entrada['email'] ?? '');

    $erros = [];

    if (strlen($nome) < 3) {
        $erros['nome'] = 'Informe o nome completo do paciente.';
    }

    if (!validarCpf($cpf)) {
        $erros['cpf'] = 'CPF inválido.';
    }

    if (empty($dataNascimento)) {
        $erros['data_nascimento'] = 'Informe a data de nascimento.';
    } elseif ($dataNascimento > date('Y-m-d')) {
        $erros['data_nascimento'] = 'A data de nascimento não pode ser futura.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'E-mail inválido.';
    }

    if ($erros) {
        erro('Dados inválidos.', 422, $erros);
    }

    $sql = "INSERT INTO pacientes (nome, cpf, data_nascimento, telefone, email)
            VALUES (:nome, :cpf, :data_nascimento, :telefone, :email)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':cpf' => $cpf,
            ':data_nascimento' => $dataNascimento,
            ':telefone' => $telefone ?: null,
            ':email' => $email ?: null
        ]);

        responder([
            'mensagem' => 'Paciente cadastrado com sucesso.',
            'id' => (int) $pdo->lastInsertId()
        ], 201);

    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            erro('Já existe um paciente cadastrado com este CPF.', 409);
        }

        erro('Não foi possível cadastrar o paciente.', 500);
    }
}

erro('Método não suportado neste recurso.', 405);