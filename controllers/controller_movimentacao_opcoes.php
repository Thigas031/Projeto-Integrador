<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['PORTEIRO']);

header('Content-Type: application/json; charset=utf-8');

function responderMovimentacaoOpcoes($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderMovimentacaoOpcoes(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

try {
    $stmtSalas = $pdo->prepare(
        "SELECT id_sala AS id, nome AS name, numero, bloco
         FROM salas
         ORDER BY nome ASC"
    );
    $stmtSalas->execute([]);

    $stmtUsuarios = $pdo->prepare(
        "SELECT id_usuario AS id, nome AS name, cargo AS role
         FROM usuarios
        WHERE ativo = 1 AND cargo <> 'Outro'
         ORDER BY nome ASC"
    );
    $stmtUsuarios->execute([]);

    $stmtChaves = $pdo->prepare(
        "SELECT k.id_chave AS id, k.id_sala AS salaId, k.nome_chave AS name,
                s.nome AS roomName, s.numero AS roomNumber, s.bloco AS block,
                k.numero_chave AS number, k.status_chaves AS status
         FROM chaves k
         INNER JOIN salas s ON s.id_sala = k.id_sala
         WHERE k.ativo = 1
           AND k.status_chaves = 'Disponivel'
           AND NOT EXISTS (
               SELECT 1 FROM movimentacoes m
               WHERE m.id_chave = k.id_chave AND m.data_devolucao IS NULL
           )
         ORDER BY s.nome ASC, k.numero_chave ASC"
    );
    $stmtChaves->execute([]);

    responderMovimentacaoOpcoes(200, [
        'ok' => true,
        'data' => [
            'salas' => $stmtSalas->fetchAll(PDO::FETCH_ASSOC),
            'usuarios' => $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC),
            'chaves' => $stmtChaves->fetchAll(PDO::FETCH_ASSOC),
        ],
    ]);
} catch (PDOException $erro) {
    error_log('Falha ao carregar opções de movimentação: ' . $erro->getMessage());
    responderMovimentacaoOpcoes(500, ['ok' => false, 'message' => 'Não foi possível carregar as opções de retirada.']);
}
