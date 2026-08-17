<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Sistema de Agendamentos' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/sistema-agendamentos/index.php">
            Sistema de Agendamentos
        </a>

        <?php if (isset($_SESSION['usuario_id'])): ?>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white small">
                <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
                (<?= htmlspecialchars($_SESSION['usuario_perfil']) ?>)
            </span>
            <a href="/sistema-agendamentos/auth/logout.php" class="btn btn-sm btn-outline-light">Sair</a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<div class="container">