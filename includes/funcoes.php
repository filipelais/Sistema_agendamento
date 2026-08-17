<?php

/**
 * Remove qualquer caractere que não seja número.
 */
function apenasNumeros(string $valor): string {
    return preg_replace('/\D/', '', $valor);
}

/**
 * Valida um CPF conferindo os dígitos verificadores.
 */
function validarCpf(string $cpf): bool {
    $cpf = apenasNumeros($cpf);

    if (strlen($cpf) !== 11) {
        return false;
    }

    // Rejeita sequências repetidas (111.111.111-11, etc.)
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    // Calcula e confere os dois dígitos verificadores
    for ($posicao = 9; $posicao < 11; $posicao++) {
        $soma = 0;

        for ($i = 0; $i < $posicao; $i++) {
            $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
        }

        $digito = ((10 * $soma) % 11) % 10;

        if ((int) $cpf[$posicao] !== $digito) {
            return false;
        }
    }

    return true;
}

/**
 * Formata um CPF para exibição: 12345678901 vira 123.456.789-01
 */
function formatarCpf(string $cpf): string {
    $cpf = apenasNumeros($cpf);

    if (strlen($cpf) !== 11) {
        return $cpf;
    }

    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

/**
 * Gera (ou recupera) o token CSRF da sessão.
 */
function tokenCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Confere se o token recebido do formulário é válido.
 */
function validarCsrf(?string $token): bool {
    return !empty($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Atalho para escapar saída em HTML.
 */
function e(?string $texto): string {
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}
/**
 * Verifica se já existe agendamento conflitante para o profissional
 * no intervalo informado.
 *
 * Dois atendimentos conflitam quando seus intervalos se sobrepõem.
 * Agendamentos cancelados são ignorados.
 *
 * @param int|null $ignorarId ID a desconsiderar (usado na edição)
 */
function existeConflito(
    PDO $pdo,
    int $usuarioId,
    string $dataHora,
    int $duracaoMinutos,
    ?int $ignorarId = null
): bool {
    $sql = "SELECT COUNT(*) AS total
            FROM agendamentos
            WHERE usuario_id = :usuario_id
              AND status <> 'cancelado'
              AND :inicio < DATE_ADD(data_hora, INTERVAL duracao_minutos MINUTE)
              AND DATE_ADD(:inicio, INTERVAL :duracao MINUTE) > data_hora";

    if ($ignorarId !== null) {
        $sql .= " AND id <> :ignorar_id";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(':inicio', $dataHora);
    $stmt->bindValue(':duracao', $duracaoMinutos, PDO::PARAM_INT);

    if ($ignorarId !== null) {
        $stmt->bindValue(':ignorar_id', $ignorarId, PDO::PARAM_INT);
    }

    $stmt->execute();

    return (int) $stmt->fetch()['total'] > 0;
}

/**
 * Retorna a lista de profissionais ativos para seleção.
 */
function listarProfissionais(PDO $pdo): array {
    return $pdo->query(
        "SELECT id, nome FROM usuarios WHERE ativo = TRUE ORDER BY nome ASC"
    )->fetchAll();
}