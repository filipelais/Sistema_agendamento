<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$filtroData = $_GET['data'] ?? '';
$filtroStatus = $_GET['status'] ?? '';
$filtroProfissional = (int) ($_GET['profissional'] ?? 0);

$condicoes = [];
$parametros = [];

if ($filtroData !== '') {
    $condicoes[] = "DATE(a.data_hora) = :data";
    $parametros[':data'] = $filtroData;
}

if (in_array($filtroStatus, ['agendado', 'realizado', 'cancelado'], true)) {
    $condicoes[] = "a.status = :status";
    $parametros[':status'] = $filtroStatus;
}

if ($filtroProfissional > 0) {
    $condicoes[] = "a.usuario_id = :profissional";
    $parametros[':profissional'] = $filtroProfissional;
}

$where = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

$sql = "SELECT a.id, a.data_hora, a.duracao_minutos, a.tipo_atendimento, a.status, a.observacoes, p.nome AS paciente_nome, p.cpf AS paciente_cpf, u.nome AS profissional_nome
        FROM agendamentos a
        INNER JOIN pacientes p ON p.id = a.paciente_id
        INNER JOIN usuarios u ON u.id = a.usuario_id
        {$where}
        ORDER BY a.data_hora ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$agendamentos = $stmt->fetchAll();

$profissionais = listarProfissionais($pdo);

$corStatus = ['agendado' => 'primary', 'realizado' => 'success', 'cancelado' => 'secondary'];

$titulo = 'Agendamentos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Agendamentos</h1>
    <a href="cadastrar.php" class="btn btn-primary">Novo agendamento</a>
</div>

<?php if (isset($_GET['sucesso'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php $mensagens = ['cadastrado' => 'Agendamento registrado com sucesso.', 'atualizado' => 'Agendamento atualizado com sucesso.', 'status' => 'Situação do agendamento atualizada.']; echo e($mensagens[$_GET['sucesso']] ?? 'Operação realizada.'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Data</label>
                <input type="date" name="data" class="form-control" value="<?= e($filtroData) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Situação</label>
                <select name="status" class="form-select">
                    <option value="">Todas</option>
                    <option value="agendado" <?= $filtroStatus === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                    <option value="realizado" <?= $filtroStatus === 'realizado' ? 'selected' : '' ?>>Realizado</option>
                    <option value="cancelado" <?= $filtroStatus === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Profissional</label>
                <select name="profissional" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($profissionais as $profissional): ?>
                        <option value="<?= (int) $profissional['id'] ?>" <?= $filtroProfissional === (int) $profissional['id'] ? 'selected' : '' ?>><?= e($profissional['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary flex-grow-1">Filtrar</button>
                <a href="listar.php" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data / Horário</th>
                    <th>Paciente</th>
                    <th>Profissional</th>
                    <th>Tipo</th>
                    <th>Situação</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($agendamentos)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Nenhum agendamento encontrado.</td></tr>
                <?php endif; ?>

                <?php foreach ($agendamentos as $agendamento): ?>
                    <?php $inicio = strtotime($agendamento['data_hora']); ?>
                    <tr>
                        <td class="text-nowrap">
                            <strong><?= date('d/m/Y', $inicio) ?></strong><br>
                            <span class="small text-muted"><?= date('H:i', $inicio) ?> às <?= date('H:i', strtotime("+{$agendamento['duracao_minutos']} minutes", $inicio)) ?></span>
                        </td>
                        <td>
                            <?= e($agendamento['paciente_nome']) ?><br>
                            <span class="small text-muted"><?= e(formatarCpf($agendamento['paciente_cpf'])) ?></span>
                        </td>
                        <td><?= e($agendamento['profissional_nome']) ?></td>
                        <td><?= e($agendamento['tipo_atendimento']) ?></td>
                        <td><span class="badge text-bg-<?= $corStatus[$agendamento['status']] ?>"><?= ucfirst(e($agendamento['status'])) ?></span></td>
                        <td class="text-end text-nowrap">
                            <?php if ($agendamento['status'] === 'agendado'): ?>
                                <a href="status.php?id=<?= (int) $agendamento['id'] ?>&acao=realizado&csrf_token=<?= e(tokenCsrf()) ?>" class="btn btn-sm btn-outline-success">Concluir</a>
                                <a href="status.php?id=<?= (int) $agendamento['id'] ?>&acao=cancelado&csrf_token=<?= e(tokenCsrf()) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancelar este agendamento?')">Cancelar</a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-center text-muted small mt-3"><?= count($agendamentos) ?> agendamento(s)</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>