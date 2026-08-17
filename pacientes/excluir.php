<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

// Mesmo sendo um link (GET), validamos o token: a ação altera dados
if (!validarCsrf($_GET['csrf_token'] ?? null)) {
    header('Location: listar.php?erro=token_invalido');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar.php');
    exit;
}

// Impede excluir paciente com histórico de agendamentos
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM agendamentos WHERE paciente_id = :id");
$stmt->execute([':id' => $id]);

if ((int) $stmt->fetch()['total'] > 0) {
    header('Location: listar.php?erro=possui_agendamentos');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM pacientes WHERE id = :id");
$stmt->execute([':id' => $id]);

header('Location: listar.php?sucesso=excluido');
exit;