<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/funcoes.php';

$titulo = 'Painel';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['erro']) && $_GET['erro'] === 'sem_permissao'): ?>
    <div class="alert alert-warning">Você não tem permissão para acessar essa área.</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Painel</h1>
    <div class="d-flex gap-2">
        <a href="/sistema-agendamentos/pacientes/listar.php" class="btn btn-outline-primary">Pacientes</a>
        <a href="/sistema-agendamentos/pacientes/buscar.php" class="btn btn-outline-primary">Busca rápida</a>
        <a href="/sistema-agendamentos/agendamentos/listar.php" class="btn btn-primary">Agendamentos</a>
        <?php if (ehAdmin()): ?>
            <a href="/sistema-agendamentos/usuarios/listar.php" class="btn btn-outline-secondary">Usuários</a>
        <?php endif; ?>
    </div>
</div>

<div id="aviso-erro" class="alert alert-danger d-none">
    Não foi possível carregar os indicadores.
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Pacientes</p>
                <p class="h2 mb-0" id="total-pacientes">—</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <p class="text-muted small mb-1">Agendados</p>
                <p class="h2 mb-0 text-primary" id="total-agendados">—</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <p class="text-muted small mb-1">Realizados</p>
                <p class="h2 mb-0 text-success" id="total-realizados">—</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-secondary">
            <div class="card-body">
                <p class="text-muted small mb-1">Cancelados</p>
                <p class="h2 mb-0 text-secondary" id="total-cancelados">—</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-muted mb-3">Agendamentos nos últimos 14 dias</h2>
                <div style="height: 280px;">
                    <canvas id="grafico-dias"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body" id="container-tipos">
                <h2 class="h6 text-muted mb-3">Tipos de atendimento</h2>
                <div style="height: 280px;">
                    <canvas id="grafico-tipos"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/sistema-agendamentos/assets/js/dashboard.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>