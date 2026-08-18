<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

exigirAdmin();

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, nome, email, perfil FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: listar.php');
    exit;
}

$ehProprioUsuario = ((int) $usuario['id'] === (int) $_SESSION['usuario_id']);

$erros = [];
$dados = $usuario;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erros['geral'] = 'Sessão expirada. Envie o formulário novamente.';
    }

    $dados['nome'] = trim($_POST['nome'] ?? '');
    $dados['email'] = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmacao = $_POST['confirmacao'] ?? '';

    // O próprio administrador não pode rebaixar seu perfil,
    // para não perder o acesso à gestão do sistema
    $dados['perfil'] = $ehProprioUsuario ? $usuario['perfil'] : ($_POST['perfil'] ?? '');

    if (strlen($dados['nome']) < 3) {
        $erros['nome'] = 'Informe o nome completo.';
    }

    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'Informe um e-mail válido.';
    }

    if (!in_array($dados['perfil'], ['admin', 'atendente'], true)) {
        $erros['perfil'] = 'Selecione um perfil válido.';
    }

    // Senha é opcional na edição: só valida se foi preenchida
    $alterarSenha = ($senha !== '');

    if ($alterarSenha) {
        if (strlen($senha) < 8) {
            $erros['senha'] = 'A senha deve ter ao menos 8 caracteres.';
        } elseif ($senha !== $confirmacao) {
            $erros['confirmacao'] = 'As senhas não coincidem.';
        }
    }

    if (empty($erros)) {
        if ($alterarSenha) {
            $sql = "UPDATE usuarios SET nome = :nome, email = :email, perfil = :perfil, senha = :senha WHERE id = :id";
            $parametros = [
                ':nome' => $dados['nome'],
                ':email' => $dados['email'],
                ':perfil' => $dados['perfil'],
                ':senha' => password_hash($senha, PASSWORD_DEFAULT),
                ':id' => $id
            ];
        } else {
            $sql = "UPDATE usuarios SET nome = :nome, email = :email, perfil = :perfil WHERE id = :id";
            $parametros = [
                ':nome' => $dados['nome'],
                ':email' => $dados['email'],
                ':perfil' => $dados['perfil'],
                ':id' => $id
            ];
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($parametros);

            // Mantém os dados da sessão em sincronia ao editar a si mesmo
            if ($ehProprioUsuario) {
                $_SESSION['usuario_nome'] = $dados['nome'];
            }

            header('Location: listar.php?sucesso=atualizado');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erros['email'] = 'Já existe outro usuário com este e-mail.';
            } else {
                $erros['geral'] = 'Não foi possível salvar as alterações.';
            }
        }
    }
}

$titulo = 'Editar usuário';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Editar usuário</h1>
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
                        </div>

                        <div class="col-md-5 mb-3">
                            <label class="form-label">Perfil *</label>
                            <?php if ($ehProprioUsuario): ?>
                                <input type="text" class="form-control" value="<?= $dados['perfil'] === 'admin' ? 'Administrador' : 'Atendente' ?>" disabled>
                                <div class="form-text">Você não pode alterar o seu próprio perfil.</div>
                            <?php else: ?>
                                <select name="perfil" class="form-select <?= isset($erros['perfil']) ? 'is-invalid' : '' ?>" required>
                                    <option value="atendente" <?= $dados['perfil'] === 'atendente' ? 'selected' : '' ?>>Atendente</option>
                                    <option value="admin" <?= $dados['perfil'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                </select>
                                <?php if (isset($erros['perfil'])): ?>
                                    <div class="invalid-feedback"><?= e($erros['perfil']) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-4">

                    <p class="text-muted small">Preencha os campos abaixo apenas se desejar alterar a senha.</p>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nova senha</label>
                            <input type="password" name="senha" class="form-control <?= isset($erros['senha']) ? 'is-invalid' : '' ?>" autocomplete="new-password">
                            <?php if (isset($erros['senha'])): ?>
                                <div class="invalid-feedback"><?= e($erros['senha']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirmar nova senha</label>
                            <input type="password" name="confirmacao" class="form-control <?= isset($erros['confirmacao']) ? 'is-invalid' : '' ?>" autocomplete="new-password">
                            <?php if (isset($erros['confirmacao'])): ?>
                                <div class="invalid-feedback"><?= e($erros['confirmacao']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>