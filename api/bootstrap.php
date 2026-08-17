<?php
/**
 * Base para os endpoints da API.
 * Define o formato de resposta, valida a autenticação
 * e oferece funções auxiliares de saída.
 */

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

/**
 * Envia uma resposta JSON e encerra a execução.
 */
function responder(array $dados, int $status = 200): never {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Envia uma resposta de erro padronizada.
 */
function erro(string $mensagem, int $status = 400, array $detalhes = []): never {
    $resposta = ['erro' => $mensagem];

    if ($detalhes) {
        $resposta['detalhes'] = $detalhes;
    }

    responder($resposta, $status);
}

/**
 * Interrompe a requisição se o usuário não estiver autenticado.
 */
function exigirAutenticacao(): void {
    if (!isset($_SESSION['usuario_id'])) {
        erro('Autenticação necessária.', 401);
    }
}

/**
 * Garante que a requisição usou o método esperado.
 */
function exigirMetodo(string $metodo): void {
    if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
        erro('Método não permitido para este recurso.', 405);
    }
}

/**
 * Lê e decodifica o corpo JSON da requisição.
 */
function corpoJson(): array {
    $conteudo = file_get_contents('php://input');

    if ($conteudo === '' || $conteudo === false) {
        return [];
    }

    $dados = json_decode($conteudo, true);

    if (!is_array($dados)) {
        erro('Corpo da requisição inválido: esperado JSON.', 400);
    }

    return $dados;
}