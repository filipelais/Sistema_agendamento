<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$profissionais = listarProfissionais($pdo);
$pacientes = $pdo->query("SELECT id, nome, cpf FROM pacientes ORDER BY nome ASC")->fetchAll();

$erros = [];
$dados = [
    'paciente_id' => '',
    'usuario_id' => $_SESSION['usuario_id'],
    'data' => '',
    'hora' => '',
    'duracao_minutos' => 30,
    'tipo_atendimento' => '',
    'observacoes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erros['geral'] = 'Sessão expirada. Envie o formulário novamente.';
    }

    $dados['paciente_id'] = (int) ($_POST['paciente_id'] ?? 0);
    $dados['usuario_id'] = (int) ($_POST['usuario_id'] ?? 0);
    $dados['data'] = $_POST['data'] ?? '';
    $dados['hora'] = $_POST['hora'] ?? '';
    $dados['duracao_minutos'] = (int) ($_POST['duracao_minutos'] ?? 30);
    $dados['tipo_atendimento'] = trim($_POST['tipo_atendimento'] ?? '');
    $dados['observacoes'] = trim($_POST['observacoes'] ?? '');

    // --- Validações ---

    if ($dados['paciente_id'] <= 0) {
        $erros['paciente_id'] = 'Selecione o paciente.';
    }

    if ($dados['usuario_id'] <= 0) {
        $erros['usuario_id'] = 'Selecione o profissional responsável.';
    }

    if (empty($dados['data']) || empty($dados['hora'])) {
        $erros['data'] = 'Informe a data e o horário do atendimento.';
    }

    if ($dados['duracao_minutos'] < 10 || $dados['duracao_minutos'] > 240) {
        $erros['duracao_minutos'] = 'A duração deve ficar entre 10 e 240 minutos.';
    }

    if ($dados['tipo_atendimento'] === '') {
        $erros['tipo_atendimento'] = 'Informe o tipo de atendimento.';
    }

    // Validações que dependem da data/hora montada
    if (empty($erros)) {
        $dataHora = $dados['data'] . ' ' . $dados['hora'] . ':00';

        if (strtotime($dataHora) < time()) {
            $erros['data'] = 'Não é possível agendar em uma data ou horário passado.';
        } elseif (existeConflito($pdo, $dados['usuario_id'], $dataHora, $dados['duracao_minutos'])) {
            $erros['data'] = 'Este profissional já possui atendimento marcado nesse horário.';
        }
    }

    // --- Gravação ---

    if (empty($erros)) {
        $sql = "INSERT INTO agendamentos
                    (paciente_id, usuario_id, data_hora, duracao_minutos, tipo_atendimento, observacoes)
                VALUES
                    (:paciente_id, :usuario_id, :data_hora, :duracao, :tipo, :observacoes)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':paciente_id' => $dados['paciente_id'],
                ':usuario_id' => $dados['usuario_id'],
                ':data_hora' => $dataHora,
                ':duracao' => $dados['duracao_minutos'],
                ':tipo' => $dados['tipo_atendimento'],
                ':observacoes' => $dados['observacoes'] ?: null
            ]);

            header('Location: listar.php?sucesso=cadastrado');
            exit;

        } catch (PDOException $e) {
            $erros['geral'] = 'Não foi possível registrar o agendamento.';
        }
    }
}

$titulo = 'Novo agendamento';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Novo agendamento</h1>
            <a href="listar.php" class="btn btn-sm btn-outline-secondary">Voltar</a>
        </div>

        <?php if (!empty($erros['geral'])): ?>
            <div class="alert alert-danger"><?= e($erros['geral']) ?></div>
        <?php endif; ?>

        <?php if (empty($pacientes)): ?>
            <div class="alert alert-warning">
                Nenhum paciente cadastrado.
                <a href="../pacientes/cadastrar.php" class="alert-link">Cadastre um paciente</a> antes de criar agendamentos.
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= e(tokenCsrf()) ?>">

                    <div class="mb-3">
                        <label class="form-label">Paciente *</label>
                        <select name="paciente_id" class="form-select <?= isset($erros['paciente_id']) ? 'is-invalid' : '' ?>" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($pacientes as $paciente): ?>
                                <option value="<?= (int) $paciente['id'] ?>"
                                    <?= $dados['paciente_id'] == $paciente['id'] ? 'selected' : '' ?>>
                                    <?= e($paciente['nome']) ?> — <?= e(formatarCpf($paciente['cpf'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($erros['paciente_id'])): ?>
                            <div class="invalid-feedback"><?= e($erros['paciente_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Profissional responsável *</label>
                        <select name="usuario_id" class="form-select <?= isset($erros['usuario_id']) ? 'is-invalid' : '' ?>" required>
                            <?php foreach ($profissionais as $profissional): ?>
                                <option value="<?= (int) $profissional['id'] ?>"
                                    <?= $dados['usuario_id'] == $profissional['id'] ? 'selected' : '' ?>>
                                    <?= e($profissional['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($erros['usuario_id'])): ?>
                            <div class="invalid-feedback"><?= e($erros['usuario_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data *</label>
                            <input type="date"
                                   name="data"
                                   class="form-control <?= isset($erros['data']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($dados['data']) ?>"
                                   min="<?= date('Y-m-d') ?>"
                                   required>
                            <?php if (isset($erros['data'])): ?>
                                <div class="invalid-feedback"><?= e($erros['data']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Horário *</label>
                            <input type="time"
                                   name="hora"
                                   class="form-control"
                                   value="<?= e($dados['hora']) ?>"
                                   step="900"
                                   required>
                            <div class="form-text">Intervalos de 15 minutos.</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Duração (min) *</label>
                            <select name="duracao_minutos" class="form-select">
                                <?php foreach ([15, 30, 45, 60, 90, 120] as $opcao): ?>
                                    <option value="<?= $opcao ?>" <?= $dados['duracao_minutos'] == $opcao ? 'selected' : '' ?>>