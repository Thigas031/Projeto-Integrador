<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['GERENTE', 'PORTEIRO', 'OUTRO']);

header('Content-Type: application/json; charset=utf-8');

function responderDashboardResumo($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderDashboardResumo(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

try {
    $stmtTotais = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN m.id_movimentacao IS NULL AND k.status_chaves = 'Disponivel' THEN 1 ELSE 0 END) AS Disponivel,
            SUM(CASE WHEN m.id_movimentacao IS NOT NULL
                       AND (m.data_prevista IS NULL OR m.data_prevista >= NOW()) THEN 1 ELSE 0 END) AS Emprestada,
            SUM(CASE WHEN m.id_movimentacao IS NOT NULL AND m.data_prevista < NOW() THEN 1 ELSE 0 END) AS Atrasada
         FROM chaves k
         LEFT JOIN movimentacoes m ON m.id_movimentacao = (
             SELECT MAX(m2.id_movimentacao)
             FROM movimentacoes m2
             WHERE m2.id_chave = k.id_chave AND m2.data_devolucao IS NULL
         )
         WHERE k.ativo = 1"
    );
    $stmtTotais->execute([]);
    $totais = $stmtTotais->fetch(PDO::FETCH_ASSOC);
    $totais = [
        'Disponivel' => (int) ($totais['Disponivel'] ?? 0),
        'Emprestada' => (int) ($totais['Emprestada'] ?? 0),
        'Atrasada' => (int) ($totais['Atrasada'] ?? 0),
    ];

    $sqlMovimentacoes = "SELECT m.id_movimentacao AS id, m.id_chave AS keyId,
                                k.nome_chave AS keyName, k.numero_chave AS keyNumber,
                                s.nome AS roomName,
                                m.id_usuario AS userId,
                                COALESCE(m.nome_retirante, u.nome) AS userName,
                                COALESCE(m.cargo_retirante, u.cargo) AS userRole,
                                p.nome AS porterName, m.data_retirada AS withdrawnAt,
                                m.data_prevista AS dueAt, m.data_devolucao AS returnedAt,
                                m.observacao AS notes,
                                CASE
                                    WHEN m.data_devolucao IS NOT NULL THEN 'Devolvida'
                                    WHEN m.data_prevista IS NOT NULL AND m.data_prevista < NOW() THEN 'Atrasada'
                                    ELSE 'Emprestada'
                                END AS status
                         FROM movimentacoes m
                         INNER JOIN chaves k ON k.id_chave = m.id_chave
                         INNER JOIN salas s ON s.id_sala = k.id_sala
                         LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario
                         INNER JOIN usuarios p ON p.id_usuario = m.id_porteiro
                         ORDER BY m.data_retirada DESC
                         LIMIT 10";
    $stmtMovimentacoes = $pdo->prepare($sqlMovimentacoes);
    $stmtMovimentacoes->execute([]);

    $sqlAbertas = "SELECT m.id_movimentacao AS id, m.id_chave AS keyId,
                          k.nome_chave AS keyName, k.numero_chave AS keyNumber,
                          s.nome AS roomName,
                          COALESCE(m.nome_retirante, u.nome) AS userName,
                          COALESCE(m.cargo_retirante, u.cargo) AS userRole,
                          m.data_retirada AS withdrawnAt,
                          m.data_prevista AS dueAt,
                          CASE
                              WHEN m.data_prevista IS NOT NULL AND m.data_prevista < NOW()
                                  THEN TIMESTAMPDIFF(SECOND, m.data_prevista, NOW())
                              ELSE 0
                          END AS lateSeconds
                   FROM movimentacoes m
                   INNER JOIN chaves k ON k.id_chave = m.id_chave
                   INNER JOIN salas s ON s.id_sala = k.id_sala
                   LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario
                   WHERE m.data_devolucao IS NULL";
    $stmtHoje = $pdo->prepare(
        $sqlAbertas . " AND m.data_prevista >= CURDATE()
                       AND m.data_prevista < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                       ORDER BY m.data_prevista ASC"
    );
    $stmtHoje->execute([]);

    $stmtAtrasadas = $pdo->prepare(
        $sqlAbertas . " AND m.data_prevista IS NOT NULL AND m.data_prevista < NOW()
                       ORDER BY m.data_prevista ASC"
    );
    $stmtAtrasadas->execute([]);

    responderDashboardResumo(200, [
        'ok' => true,
        'data' => [
            'totais' => $totais,
            'ultimas_movimentacoes' => $stmtMovimentacoes->fetchAll(PDO::FETCH_ASSOC),
            'chaves_devolver_hoje' => $stmtHoje->fetchAll(PDO::FETCH_ASSOC),
            'atrasadas' => $stmtAtrasadas->fetchAll(PDO::FETCH_ASSOC),
        ],
    ]);
} catch (PDOException $erro) {
    error_log('Falha ao carregar resumo do dashboard: ' . $erro->getMessage());
    responderDashboardResumo(500, ['ok' => false, 'message' => 'Não foi possível carregar o resumo do dashboard.']);
}
