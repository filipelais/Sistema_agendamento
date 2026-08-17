<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar.php');
    exit;
}

// Carrega o paciente
$stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = :id");
$stmt->execute([':id' => $id]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header('Location: listar.php?erro=nao_encontrado');
    exit;
}

$erros = [];
$dados = $paciente;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erros['geral'] = 'Sessão expirada. Envie o formulário novamente.';
    }

    $dados['nome'] = trim($_POST['nome'] ?? '');
    $dados['cpf'] = apenasNumeros($_POST['cpf'] ?? '');
    $dados['data_nascimento'] = $_POST['data_nascimento'] ?? '';
    $dados['telefone'] = trim($_POST['telefone'] ?? '');
    $dados['email'] = trim($_POST['email'] ?? '');

    if (strlen($dados['nome']) < 3) {
        $erros['nome'] = 'Informe o nome completo do paciente.';
    }

    if (!validarCpf($dados['cpf'])) {
        $erros['cpf'] = 'CPF inválido.';
    }

    if (empty($dados['data_nascimento'])) {
        $erros['data_nascimento'] = 'Informe a data de nascimento.';
    } elseif ($dados['data_nascimento'] > date('Y-m-d')) {
        $erros['data_nascimento'] = 'A data de nascimento não pode ser futura.';
    }

    if (!empty($dados['email']) && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'E-mail inválido.';
    }

    if (empty($erros)) {
        $sql = "UPDATE pacientes
                SET nome = :nome,
                    cpf = :cpf,
                    data_nascimento = :data_nascimento,
                    telefone = :telefone,
                    email = :email
                WHERE id = :id";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':cpf' => $dados['cpf'],
                ':data_nascimento' => $dados['data_nascimento'],
                ':telefone' => $dados['telefone'] ?: null,
                ':email' => $dados['email'] ?: null,
                ':id' => $id
            ]);

            header('Location: listar.php?sucesso=atualizado');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erros['cpf'] = 'Já existe outro paciente cadastrado com este CPF.';
            } else {
                $erros['geral'] = 'Não foi possível salvar as alterações.';
            }
        }
    }
}

$titulo = 'Editar paciente';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Editar paciente</h1>
            <a href="listar.php" class="btn btn-sm btn-outline-secondary">Voltar</a>
        </div>

        <?php if (!empty($erros['geral'])): ?>
            <div class="alert alert-danger"><?= e($erros['geral']) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= e(tokenCsrf()) ?>">

                    <div class="mb-3">
                        <label class="form-label">Nome completo *</label>
                        <input type="text"
                               name="nome"
                               class="form-control <?= isset($erros['nome']) ? 'is-invalid' : '' ?>"
                               value="<?= e($dados['nome']) ?>"
                               required>
                        <?php if (isset($erros['nome'])): ?>
                            <div class="invalid-feedback"><?= e($erros