<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

exigirAdmin();

if (!validarCsrf($_GET['csrf_token'] ?? null)) {
    header('Location: listar.php?erro=token_invalido');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar.php');
    exit;
}

// Impede que o administrador desative o próprio acesso
if ($id === (int) $_SESSION['usuario_id']) {
    header('Location: listar.php?erro=proprio_usuario');
    exit;
}

// Inverte a situação atual
$stmt = $pdo->prepare("UPDATE usuarios SET ativo = NOT ativo WHERE id = :id");
$stmt->execute([':id' => $id]);

header('Location: listar.php?sucesso=status');
exit;