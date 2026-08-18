<php
<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

exigirAdmin();

$erros = [];
$dados = ['nome' => '', 'email' => '', 'perfil' => 'atendente'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erros['geral'] = 'Sessão expirada. Envie o formulário novamente.';
    }

    $dados['nome'] = trim($_POST['nome'] ?? '');
    $dados['email'] = trim($_POST['email'] ?? '');
    $dados['perfil'] = $_POST['perfil'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmacao = $_POST['confirmacao'] ?? '';

    if (strlen($dados['nome']) < 3) {
        $erros['nome'] = 'Informe o nome completo.';
    }

    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'Informe um e-mail válido.';
    }

    // Lista branca: só aceita perfis previstos
    if (!in_array($dados['perfil'], ['admin', 'atendente'], true)) {
        $erros['perfil'] = 'Selecione um perfil válido.';
    }

    if (strlen($senha) < 8) {
        $erros['senha'] = 'A senha deve ter ao menos 8 caracteres.';
    } elseif ($senha !== $confirmacao) {
        $erros['confirmacao'] = 'As senhas não coincidem.';
    }

    if (empty($erros)) {
        $sql = "INSERT INTO usuarios (nome, email, senha, perfil) VALUES (:nome, :email, :senha, :perfil)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':email' => $dados['email'],
                ':senha' => password_hash($senha, PASSWORD_DEFAULT),
                ':perfil' => $dados['perfil']
            ]);

            header('Location: listar.php?sucesso=cadastrado');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erros['email'] = 'Já existe um usuário com este e-mail.';
            } else {
                $erros['geral'] = 'Não foi possível cadastrar o usuário.';
            }
        }
    }
}

$titulo = 'Novo usuário';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Novo usuário</h1>
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
                        <input type="text" name="nome" class="form-control <?= isset($erros['nome']) ? 'is-invalid' : '' ?>" value="<?= e($dados['nome']) ?>" required>
                        <?php if (isset($erros['nome'])): ?>
                            <div class="invalid-feedback"><?= e($erros['nome']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="email" class="form-control <?= isset($erros['email']) ? 'is-invalid' : '' ?>" value="<?= e($dados['email']) ?>" required>
                            <?php if (isset($erros['email'])): ?>
                                <div class="invalid-feedback"><?= e($erros['email']) ?></div>
                            <?php endif; ?>
                            <div class="form-text">Será usado para o login.</div>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label class="form-label">Perfil *</label>
                            <select name="perfil" class="form-select <?= isset($erros['perfil']) ? 'is-invalid' : '' ?>" required>
                                <option value="atendente" <?= $dados['perfil'] === 'atendente' ? 'selected' : '' ?>>Atendente</option>
                                <option value="admin" <?= $dados['perfil'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                            <?php if (isset($erros['perfil'])): ?>
                                <div class="invalid-feedback"><?= e($erros['perfil']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Senha *</label>
                            <input type="password" name="senha" class="form-control <?= isset($erros['senha']) ? 'is-invalid' : '' ?>" minlength="8" required>
                            <?php if (isset($erros['senha'])): ?>
                                <div class="invalid-feedback"><?= e($erros['senha']) ?></div>
                            <?php endif; ?>
                            <div class="form-text">Mínimo de 8 caracteres.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirmar senha *</label>
                            <input type="password" name="confirmacao" class="form-control <?= isset($erros['confirmacao']) ? 'is-invalid' : '' ?>" minlength="8" required>
                            <?php if (isset($erros['confirmacao'])): ?>
                                <div class="invalid-feedback"><?= e($erros['confirmacao']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>