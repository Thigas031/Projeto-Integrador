<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['GERENTE', 'PORTEIRO']);

header('Content-Type: application/json; charset=utf-8');

function responderChaveListar($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderChaveListar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$statusFiltro = $_GET['status'] ?? '';
$busca = $_GET['busca'] ?? '';
$idChave = $_GET['chave'] ?? '';
$pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);

if (!is_string($statusFiltro) || !is_string($busca) || !is_string($idChave) ||
    $pagina === false || $pagina < 1 || strlen($busca) > 100) {
    responderChaveListar(400, ['ok' => false, 'message' => 'Filtros inválidos.']);
}

$statusPermitidos = ['', 'Disponivel', 'Emprestada', 'Atrasada', 'Abertas'];
if (!in_array($statusFiltro, $statusPermitidos, true)) {
    responderChaveListar(400, ['ok' => false, 'message' => 'Status inválido.']);
}

$filtros = ['k.ativo = 1'];
$parametros = [];

if ($busca !== '') {
    $filtros[] = '(s.nome LIKE :busca_nome OR s.numero LIKE :busca_numero OR k.numero_chave LIKE :busca_chave)';
    $termo = '%' . $busca . '%';
    $parametros[':busca_nome'] = $termo;
    $parametros[':busca_numero'] = $termo;
    $parametros[':busca_chave'] = $termo;
}

switch ($statusFiltro) {
    case 'Disponivel':
        $filtros[] = "k.status_chaves = 'Disponivel' AND NOT EXISTS (
            SELECT 1 FROM movimentacoes mo
            WHERE mo.id_chave = k.id_chave AND mo.data_devolucao IS NULL
        )";
        break;
    case 'Emprestada':
        $filtros[] = "EXISTS (
            SELECT 1 FROM movimentacoes me
            WHERE me.id_chave = k.id_chave AND me.data_devolucao IS NULL
              AND (me.data_prevista IS NULL OR me.data_prevista >= NOW())
        )";
        break;
    case 'Atrasada':
        $filtros[] = "EXISTS (
            SELECT 1 FROM movimentacoes ma
            WHERE ma.id_chave = k.id_chave AND ma.data_devolucao IS NULL
              AND ma.data_prevista < NOW()
        )";
        break;
    case 'Abertas':
        $filtros[] = "EXISTS (
            SELECT 1 FROM movimentacoes mab
            WHERE mab.id_chave = k.id_chave AND mab.data_devolucao IS NULL
        )";
        break;
}

$where = implode(' AND ', $filtros);
$sqlBase = "FROM chaves k
            INNER JOIN salas s ON s.id_sala = k.id_sala
            LEFT JOIN movimentacoes m ON m.id_movimentacao = (
                SELECT MAX(m2.id_movimentacao)
                FROM movimentacoes m2
                WHERE m2.id_chave = k.id_chave AND m2.data_devolucao IS NULL
            )
            LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario
            WHERE $where";

try {
    if ($idChave !== '') {
        $id = filter_var($idChave, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            responderChaveListar(400, ['ok' => false, 'message' => 'Identificador de chave inválido.']);
        }

        $stmt = $pdo->prepare(
            "SELECT k.id_chave AS id, k.id_sala AS salaId, s.nome AS name,
                    s.numero AS roomNumber, s.bloco AS block,
                    k.numero_chave AS number,
                    CASE
                        WHEN m.id_movimentacao IS NULL THEN k.status_chaves
                        WHEN m.data_prevista IS NOT NULL AND m.data_prevista < NOW() THEN 'Atrasada'
                        ELSE 'Emprestada'
                    END AS status,
                    u.nome AS responsible
             $sqlBase AND k.id_chave = :id
             LIMIT 1"
        );
        $parametros[':id'] = $id;
        $stmt->execute($parametros);
        $chave = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$chave) {
            responderChaveListar(404, ['ok' => false, 'message' => 'Chave não encontrada.']);
        }
        responderChaveListar(200, ['ok' => true, 'data' => $chave]);
    }

    $stmtTotal = $pdo->prepare("SELECT COUNT(DISTINCT k.id_chave) $sqlBase");
    $stmtTotal->execute($parametros);
    $total = (int) $stmtTotal->fetchColumn();
    $offset = ($pagina - 1) * 5;

    $stmt = $pdo->prepare(
        "SELECT k.id_chave AS id, k.id_sala AS salaId, s.nome AS name,
                s.numero AS roomNumber, s.bloco AS block,
                k.numero_chave AS number,
                CASE
                    WHEN m.id_movimentacao IS NULL THEN k.status_chaves
                    WHEN m.data_prevista IS NOT NULL AND m.data_prevista < NOW() THEN 'Atrasada'
                    ELSE 'Emprestada'
                END AS status,
                u.nome AS responsible
         $sqlBase
         ORDER BY s.nome ASC, k.numero_chave ASC
         LIMIT :limite OFFSET :deslocamento"
    );
    $parametrosPaginacao = $parametros + [
        ':limite' => 5,
        ':deslocamento' => $offset,
    ];
    $stmt->execute($parametrosPaginacao);

    responderChaveListar(200, [
        'ok' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => 5,
    ]);
} catch (PDOException $erro) {
    error_log('Falha ao listar chaves: ' . $erro->getMessage());
    responderChaveListar(500, ['ok' => false, 'message' => 'Não foi possível carregar as chaves.']);
}
