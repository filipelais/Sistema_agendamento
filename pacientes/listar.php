<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$busca = trim($_GET['busca'] ?? '');
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

// Monta o filtro de busca (por nome ou CPF)
$where = '';
$parametros = [];

if ($busca !== '') {
    $where = "WHERE nome LIKE :busca OR cpf LIKE :buscaCpf";
    $parametros[':busca'] = '%' . $busca . '%';
    $parametros[':buscaCpf'] = '%' . apenasNumeros($busca) . '%';
}

// Conta o total de registros para calcular as páginas
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM pacientes {$where}");
$stmt->execute($parametros);
$totalRegistros = (int) $stmt->fetch()['total'];
$totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));

// Busca os registros da página atual
$sql = "SELECT id, nome, cpf, data_nascimento, telefone, email
        FROM pacientes
        {$where}
        ORDER BY nome ASC
        LIMIT :limite OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($parametros as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

// LIMIT e OFFSET exigem bind como inteiro
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$pacientes = $stmt->fetchAll();

$titulo = 'Pacientes';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Pacientes</h1>
    <a href="cadastrar.php" class="btn btn-primary">Novo paciente</a>
</div>

<?php if (isset($_GET['sucesso'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php
        $mensagens = [
            'cadastrado' => 'Paciente cadastrado com sucesso.',
            'atualizado' => 'Dados atualizados com sucesso.',
            'excluido'   => 'Paciente excluído com sucesso.'
        ];
        echo e($mensagens[$_GET['sucesso']] ?? 'Operação realizada.');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['erro']) && $_GET['erro'] === 'possui_agendamentos'): ?>
    <div class="alert alert-warning">
        Não é possível excluir um paciente que possui agendamentos registrados.
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-2">
            <div class="col">
                <input type="text"
                       name="busca"
                       class="form-control"
                       placeholder="Buscar por nome ou CPF"
                       value="<?= e($busca) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Buscar</button>
            </div>
            <?php if ($busca !== ''): ?>
                <div class="col-auto">
                    <a href="listar.php" class="btn btn-outline-secondary">Limpar</a>
                </div>
            <?php endif; ?>
        </form>
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
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pacientes)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <?= $busca !== '' ? 'Nenhum paciente encontrado para esta busca.' : 'Nenhum paciente cadastrado ainda.' ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($pacientes as $paciente): ?>
                    <tr>
                        <td><?= e($paciente['nome']) ?></td>
                        <td><?= e(formatarCpf($paciente['cpf'])) ?></td>
                        <td><?= e(date('d/m/Y', strtotime($paciente['data_nascimento']))) ?></td>
                        <td class="small text-muted">
                            <?= e($paciente['telefone'] ?: '—') ?><br>
                            <?= e($paciente['email'] ?: '—') ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="editar.php?id=<?= (int) $paciente['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                            <a href="excluir.php?id=<?= (int) $paciente['id'] ?>&csrf_token=<?= e(tokenCsrf()) ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Excluir o paciente <?= e($paciente['nome']) ?>?')">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPaginas > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $i ?>&busca=<?= urlencode($busca) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<p class="text-center text-muted small mt-3">
    <?= $totalRegistros ?> paciente(s) encontrado(s)
</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>