<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

// Área restrita a administradores
exigirAdmin();

$usuarios = $pdo->query("SELECT id, nome, email, perfil, ativo, criado_em FROM usuarios ORDER BY nome ASC")->fetchAll();

$titulo = 'Usuários do sistema';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Usuários do sistema</h1>
    <a href="cadastrar.php" class="btn btn-primary">Novo usuário</a>
</div>

<?php if (isset($_GET['sucesso'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php $mensagens = ['cadastrado' => 'Usuário cadastrado com sucesso.', 'atualizado' => 'Usuário atualizado com sucesso.', 'status' => 'Situação do usuário alterada.']; echo e($mensagens[$_GET['sucesso']] ?? 'Operação realizada.'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['erro']) && $_GET['erro'] === 'proprio_usuario'): ?>
    <div class="alert alert-warning">Você não pode desativar o seu próprio acesso.</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Situação</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr class="<?= $usuario['ativo'] ? '' : 'opacity-50' ?>">
                        <td>
                            <?= e($usuario['nome']) ?>
                            <?php if ((int) $usuario['id'] === (int) $_SESSION['usuario_id']): ?>
                                <span class="badge text-bg-light border ms-1">você</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= e($usuario['email']) ?></td>
                        <td>
                            <span class="badge text-bg-<?= $usuario['perfil'] === 'admin' ? 'dark' : 'light border' ?>">
                                <?= $usuario['perfil'] === 'admin' ? 'Administrador' : 'Atendente' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge text-bg-<?= $usuario['ativo'] ? 'success' : 'secondary' ?>">
                                <?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="editar.php?id=<?= (int) $usuario['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                            <?php if ((int) $usuario['id'] !== (int) $_SESSION['usuario_id']): ?>
                                <a href="situacao.php?id=<?= (int) $usuario['id'] ?>&csrf_token=<?= e(tokenCsrf()) ?>" class="btn btn-sm btn-outline-<?= $usuario['ativo'] ? 'danger' : 'success' ?>" onclick="return confirm('<?= $usuario['ativo'] ? 'Desativar' : 'Reativar' ?> o acesso de <?= e($usuario['nome']) ?>?')"><?= $usuario['ativo'] ? 'Desativar' : 'Reativar' ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-center text-muted small mt-3"><?= count($usuarios) ?> usuário(s)</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>