<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['PORTEIRO']);

header('Content-Type: application/json; charset=utf-8');

function responderMovimentacaoBuscar($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderMovimentacaoBuscar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$abertas = $_GET['abertas'] ?? '';
$idChave = $_GET['chave'] ?? '';
$numero = $_GET['numero'] ?? '';
$sala = $_GET['sala'] ?? '';

if (!is_string($abertas) || !is_string($idChave) || !is_string($numero) || !is_string($sala) ||
    strlen($numero) > 30 || strlen($sala) > 100) {
    responderMovimentacaoBuscar(400, ['ok' => false, 'message' => 'Filtros inválidos.']);
}

$where = ['m.data_devolucao IS NULL'];
$parametros = [];

if ($abertas !== '1' && $idChave !== '') {
    $id = filter_var($idChave, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        responderMovimentacaoBuscar(400, ['ok' => false, 'message' => 'Identificador de chave inválido.']);
    }
    $where[] = 'k.id_chave = :id_chave';
    $parametros[':id_chave'] = $id;
}

if ($numero !== '') {
    $where[] = 'k.numero_chave LIKE :numero';
    $parametros[':numero'] = '%' . $numero . '%';
}

if ($sala !== '') {
    $where[] = '(s.nome LIKE :sala_nome OR s.numero LIKE :sala_numero)';
    $parametros[':sala_nome'] = '%' . $sala . '%';
    $parametros[':sala_numero'] = '%' . $sala . '%';
}

if ($abertas !== '1' && $idChave === '' && $numero === '' && $sala === '') {
    responderMovimentacaoBuscar(400, ['ok' => false, 'message' => 'Informe uma chave, sala ou solicite a lista de retiradas abertas.']);
}

try {
    $stmt = $pdo->prepare(
        "SELECT m.id_movimentacao AS id, m.id_chave AS keyId,
                k.numero_chave AS keyNumber, s.nome AS keyName,
                s.bloco AS block, m.id_usuario AS userId,
                u.nome AS userName, m.data_retirada AS withdrawnAt,
                m.data_prevista AS dueAt, m.data_devolucao AS returnedAt,
                m.observacao AS notes,
                CASE
                    WHEN m.data_prevista IS NOT NULL AND m.data_prevista < NOW() THEN 'Atrasada'
                    ELSE 'Emprestada'
                END AS status
         FROM movimentacoes m
         INNER JOIN chaves k ON k.id_chave = m.id_chave
         INNER JOIN salas s ON s.id_sala = k.id_sala
         INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
         WHERE " . implode(' AND ', $where) . "
         ORDER BY m.data_retirada DESC"
    );
    $stmt->execute($parametros);
    $movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($abertas !== '1' && !$movimentacoes) {
        responderMovimentacaoBuscar(404, ['ok' => false, 'message' => 'Não há retirada em aberto para essa chave.']);
    }

    responderMovimentacaoBuscar(200, ['ok' => true, 'data' => $abertas === '1' ? $movimentacoes : $movimentacoes[0]]);
} catch (PDOException $erro) {
    error_log('Falha ao buscar movimentação: ' . $erro->getMessage());
    responderMovimentacaoBuscar(500, ['ok' => false, 'message' => 'Não foi possível buscar a movimentação.']);
}
