<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/conexao.php';
<a href="/sistema-agendamentos/pacientes/buscar.php" class="btn btn-outline-primary">Busca rápida</a>
// Busca alguns números para o painel
$totalPacientes = $pdo->query("SELECT COUNT(*) AS total FROM pacientes")->fetch()['total'];
$totalAgendamentos = $pdo->query("SELECT COUNT(*) AS total FROM agendamentos WHERE status = 'agendado'")->fetch()['total'];
$agendamentosHoje = $pdo->query("SELECT COUNT(*) AS total FROM agendamentos WHERE DATE(data_hora) = CURDATE() AND status = 'agendado'")->fetch()['total'];

$titulo = 'Painel';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['erro']) && $_GET['erro'] === 'sem_permissao'): ?>
    <div class="alert alert-warning">Você não tem permissão para acessar essa área.</div>
<?php endif; ?>

<h1 class="h3 mb-4">Painel</h1>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted small mb-1">Pacientes cadastrados</p>
                <p class="h2 mb-0"><?= $totalPacientes ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted small mb-1">Agendamentos ativos</p>
                <p class="h2 mb-0"><?= $totalAgendamentos ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted small mb-1">Atendimentos hoje</p>
                <p class="h2 mb-0"><?= $agendamentosHoje ?></p>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 flex-wrap">
    <a href="/sistema-agendamentos/pacientes/listar.php" class="btn btn-primary">Pacientes</a>
    <a href="/sistema-agendamentos/agendamentos/listar.php" class="btn btn-primary">Agendamentos</a>
    <?php if (ehAdmin()): ?>
        <a href="/sistema-agendamentos/usuarios/listar.php" class="btn btn-outline-secondary">Usuários do sistema</a>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>