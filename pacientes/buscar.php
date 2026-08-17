<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/funcoes.php';

$titulo = 'Buscar pacientes';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Buscar pacientes</h1>
    <a href="listar.php" class="btn btn-sm btn-outline-secondary">Listagem completa</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <label class="form-label">Nome ou CPF</label>
        <input type="text"
               id="campo-busca"
               class="form-control form-control-lg"
               placeholder="Comece a digitar..."
               autocomplete="off"
               autofocus>
        <div class="form-text">Os resultados aparecem conforme você digita.</div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Nascimento</th>
                    <th>Contato</th>
                </tr>
            </thead>
            <tbody id="resultado-busca"></tbody>
        </table>
    </div>
</div>

<p class="text-center text-muted small mt-3" id="contador-resultado"></p>

<script src="/sistema-agendamentos/assets/js/busca-pacientes.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>