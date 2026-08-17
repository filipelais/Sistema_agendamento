/**
 * Painel de indicadores.
 * Consome a API de estatísticas e desenha os gráficos com Chart.js.
 */

const API_ESTATISTICAS = '/sistema-agendamentos/api/estatisticas.php';

const PALETA = {
    azul: '#0d6efd',
    verde: '#198754',
    cinza: '#adb5bd',
    laranja: '#fd7e14',
    roxo: '#6f42c1',
    ciano: '#0dcaf0'
};

/**
 * Preenche os cartões de totais.
 */
function preencherTotais(totais) {
    document.getElementById('total-pacientes').textContent = totais.pacientes;
    document.getElementById('total-agendados').textContent = totais.agendados;
    document.getElementById('total-realizados').textContent = totais.realizados;
    document.getElementById('total-cancelados').textContent = totais.cancelados;
}

/**
 * Desenha o gráfico de agendamentos por dia.
 */
function graficoPorDia(porDia) {
    new Chart(document.getElementById('grafico-dias'), {
        type: 'line',
        data: {
            labels: porDia.map(item => item.rotulo),
            datasets: [{
                label: 'Agendamentos',
                data: porDia.map(item => item.total),
                borderColor: PALETA.azul,
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
}

/**
 * Desenha o gráfico de distribuição por tipo de atendimento.
 */
function graficoPorTipo(porTipo) {
    const container = document.getElementById('container-tipos');

    if (porTipo.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-5 mb-0">Sem dados para exibir.</p>';
        return;
    }

    new Chart(document.getElementById('grafico-tipos'), {
        type: 'doughnut',
        data: {
            labels: porTipo.map(item => item.tipo),
            datasets: [{
                data: porTipo.map(item => item.total),
                backgroundColor: Object.values(PALETA)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

/**
 * Carrega os dados e monta o painel.
 */
async function carregarPainel() {
    try {
        const resposta = await fetch(API_ESTATISTICAS);

        if (!resposta.ok) {
            throw new Error(`Erro ${resposta.status}`);
        }

        const dados = await resposta.json();

        preencherTotais(dados.totais);
        graficoPorDia(dados.por_dia);
        graficoPorTipo(dados.por_tipo);

    } catch (falha) {
        console.error('Falha ao carregar o painel:', falha);
        document.getElementById('aviso-erro').classList.remove('d-none');
    }
}

carregarPainel();