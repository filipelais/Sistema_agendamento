<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloqueia acesso de quem não está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /sistema-agendamentos/auth/login.php');
    exit;
}

/**
 * Verifica se o usuário logado é administrador.
 */
function ehAdmin(): bool {
    return ($_SESSION['usuario_perfil'] ?? '') === 'admin';
}

/**
 * Bloqueia o acesso à página se o usuário não for administrador.
 */
function exigirAdmin(): void {
    if (!ehAdmin()) {
        header('Location: /sistema-agendamentos/index.php?erro=sem_permissao');
        exit;
    }
}