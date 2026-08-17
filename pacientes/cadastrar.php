<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$erros = [];
$dados = [
    'nome' => '',
    'cpf' => '',
    'data_nascimento' => '',
    'telefone' => '',
    'email' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bloqueia requisições que não vieram do nosso próprio formulário
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erros['geral'] = 'Sessão expirada. Envie o formulário novamente.';
    }

    $dados['nome'] = trim($_POST['nome'] ?? '');
    $dados['cpf'] = apenasNumeros($_POST['cpf'] ?? '');
    $dados['data_nascimento'] = $_POST['data_nascimento'] ?? '';
    $dados['telefone'] = trim($_POST['telefone'] ?? '');
    $dados['email'] = trim($_POST['email'] ?? '');

    // --- Validações ---

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

    // --- Gravação ---

    if (empty($erros)) {
        $sql = "INSERT INTO pacientes (nome, cpf, data_nascimento, telefone, email)
                VALUES (:nome, :cpf, :data_nascimento, :telefone, :email)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':cpf' => $dados['cpf'],
                ':data_nascimento' => $dados['data_nascimento'],
                ':telefone' => $dados['telefone'] ?: null,
                ':email' => $dados['email'] ?: null
            ]);

            header('Location: listar.php?sucesso=cadastrado');
            exit;

        } catch (PDOException $e) {
            // Código 23000 = violação de restrição (aqui, o UNIQUE do CPF)
            if ($e->getCode() === '23000') {
                $erros['cpf'] = 'Já existe um paciente cadastrado com este CPF.';
            } else {
                $erros['geral'] = 'Não foi possível salvar o cadastro.';
            }
        }
    }
}

$titulo = 'Cadastrar paciente';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Cadastrar paciente</h1>
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
                            <div class="invalid-feedback"><?= e($erros['nome']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CPF *</label>
                            <input type="text"
                                   name="cpf"
                                   class="form-control <?= isset($erros['cpf']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($dados['cpf']) ?>"
                                   maxlength="14"
                                   required>
                            <?php if (isset($erros['cpf'])): ?>
                                <div class="invalid-feedback"><?= e($erros['cpf']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data de nascimento *</label>
                            <input type="date"
                                   name="data_nascimento"
                                   class="form-control <?= isset($erros['data_nascimento']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($dados['data_nascimento']) ?>"
                                   required>
                            <?php if (isset($erros['data_nascimento'])): ?>
                                <div class="invalid-feedback"><?= e($erros['data_nascimento']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="telefone" class="form-control" value="<?= e($dados['telefone']) ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email"
                                   name="email"
                                   class="form-control <?= isset($erros['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($dados['email']) ?>">
                            <?php if (isset($erros['email'])): ?>
                                <div class="invalid-feedback"><?= e($erros['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>