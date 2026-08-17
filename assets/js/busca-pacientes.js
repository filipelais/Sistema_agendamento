/**
 * Busca dinâmica de pacientes.
 * Consulta a API conforme o usuário digita, sem recarregar a página.
 */

const API_URL = '/sistema-agendamentos/api/pacientes.php';

const campoBusca = document.getElementById('campo-busca');
const resultado = document.getElementById('resultado-busca');
const contador = document.getElementById('contador-resultado');

let temporizador = null;

/**
 * Escapa texto para inserção segura no HTML.
 */
function escapar(texto) {
    const elemento = document.createElement('div');
    elemento.textContent = texto ?? '';
    return elemento.innerHTML;
}

/**
 * Formata uma data ISO (2024-01-15) para o padrão brasileiro.
 */
function formatarData(dataIso) {
    if (!dataIso) return '—';
    const [ano, mes, dia] = dataIso.split('-');
    return `${dia}/${mes}/${ano}`;
}

/**
 * Monta as linhas da tabela com os pacientes recebidos.
 */
function renderizar(pacientes) {
    if (pacientes.length === 0) {
        resultado.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    Nenhum paciente encontrado.
                </td>
            </tr>`;
        return;
    }

    resultado.innerHTML = pacientes.map(paciente => `
        <tr>
            <td>${escapar(paciente.nome)}</td>
            <td>${escapar(paciente.cpf_formatado)}</td>
            <td>${formatarData(paciente.data_nascimento)}</td>
            <td class="small text-muted">
                ${escapar(paciente.telefone || '—')}<br>
                ${escapar(paciente.email || '—')}
            </td>
        </tr>
    `).join('');
}

/**
 * Consulta a API e atualiza a tabela.
 */
async function buscar(termo) {
    resultado.innerHTML = `
        <tr>
            <td colspan="4" class="text-center text-muted py-4">
                <span class="spinner-border spinner-border-sm"></span> Buscando...
            </td>
        </tr>`;

    try {
        const resposta = await fetch(`${API_URL}?busca=${encodeURIComponent(termo)}`);

        if (resposta.status === 401) {
            resultado.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-danger py-4">
                        Sessão expirada. <a href="/sistema-agendamentos/auth/login.php">Entre novamente</a>.
                    </td>
                </tr>`;
            return;
        }

        if (!resposta.ok) {
            throw new Error(`Erro ${resposta.status}`);
        }

        const json = await resposta.json();

        renderizar(json.dados);
        contador.textContent = `${json.total} paciente(s) encontrado(s)`;

    } catch (falha) {
        resultado.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-danger py-4">
                    Não foi possível carregar os pacientes.
                </td>
            </tr>`;
        console.error('Falha na busca:', falha);
    }
}

// Dispara a busca 400ms após o usuário parar de digitar
campoBusca.addEventListener('input', () => {
    clearTimeout(temporizador);
    temporizador = setTimeout(() => buscar(campoBusca.value.trim()), 400);
});

// Carrega a lista inicial
buscar('');