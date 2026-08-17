<?php
/**
 * Endpoint: /api/estatisticas.php
 *
 * GET  Retorna números consolidados para o painel:
 *      totais gerais, agendamentos por dia e distribuição por tipo.
 */

require_once __DIR__ . '/bootstrap.php';

exigirAutenticacao();
exigirMetodo('GET');

// --- Totais gerais ---

$totais = [
    'pacientes' => (int) $pdo->query("SELECT COUNT(*) AS t FROM pacientes")->fetch()['t'],
    'agendados' => (int) $pdo->query("SELECT COUNT(*) AS t FROM agendamentos WHERE status = 'agendado'")->fetch()['t'],
    'realizados' => (int) $pdo->query("SELECT COUNT(*) AS t FROM agendamentos WHERE status = 'realizado'")->fetch()['t'],
    'cancelados' => (int) $pdo->query("SELECT COUNT(*) AS t FROM agendamentos WHERE status = 'cancelado'")->fetch()['t'],
];

// --- Agendamentos dos últimos 14 dias ---

$sql = "SELECT DATE(data_hora) AS dia, COUNT(*) AS total
        FROM agendamentos
        WHERE data_hora >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
          AND data_hora < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        GROUP BY DATE(data_hora)
        ORDER BY dia ASC";

$registros = $pdo->query($sql)->fetchAll();

// Indexa por data para preencher os dias sem agendamento com zero
$porData = [];
foreach ($registros as $registro) {
    $porData[$registro['dia']] = (int) $registro['total'];
}

$porDia = [];
for ($i = 13; $i >= 0; $i--) {
    $data = date('Y-m-d', strtotime("-{$i} days"));

    $porDia[] = [
        'data' => $data,
        'rotulo' => date('d/m', strtotime($data)),
        'total' => $porData[$data] ?? 0
    ];
}

// --- Distribuição por tipo de atendimento ---

$sql = "SELECT tipo_atendimento, COUNT(*) AS total
        FROM agendamentos
        WHERE status <> 'cancelado'
        GROUP BY tipo_atendimento
        ORDER BY total DESC
        LIMIT 6";

$porTipo = array_map(
    fn(array $linha): array => [
        'tipo' => $linha['tipo_atendimento'],
        'total' => (int) $linha['total']
    ],
    $pdo->query($sql)->fetchAll()
);

responder([
    'totais' => $totais,
    'por_dia' => $porDia,
    'por_tipo' => $porTipo
]);