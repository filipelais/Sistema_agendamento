<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (!validarCsrf($_GET['csrf_token'] ?? null)) {
    header('Location: listar.php?erro=token_invalido');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$acao = $_GET['acao'] ?? '';

// Só aceita transições válidas
if ($id <= 0 || !in_array($acao, ['realizado', 'cancelado'], true)) {
    header('Location: listar.php');
    exit;
}

$stmt = $pdo->prepare(
    "UPDATE agendamentos SET status = :status WHERE id = :id AND status = 'agendado'"
);
$stmt->execute([':status' => $acao, ':id' => $id]);

header('Location: listar.php?sucesso=status');
exit;