<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['GERENTE', 'PORTEIRO']);

header('Content-Type: application/json; charset=utf-8');

function responderObservacaoListar($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderObservacaoListar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$data = $_GET['data'] ?? '';
if (!is_string($data) || ($data !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $data))) {
    responderObservacaoListar(400, ['ok' => false, 'message' => 'Data inválida.']);
}
if ($data !== '') {
    $dataValidada = DateTime::createFromFormat('!Y-m-d', $data);
    $erros = DateTime::getLastErrors();
    if (!$dataValidada || ($erros !== false && ($erros['warning_count'] > 0 || $erros['error_count'] > 0)) ||
        $dataValidada->format('Y-m-d') !== $data) {
        responderObservacaoListar(400, ['ok' => false, 'message' => 'Data inválida.']);
    }
}

try {
    if ($data === '') {
        $stmt = $pdo->prepare(
            "SELECT o.id_observacao AS id, o.texto AS text,
                    o.data_hora AS createdAt, u.nome AS createdBy,
                    o.id_porteiro AS porterId
             FROM observacoes o
             INNER JOIN usuarios u ON u.id_usuario = o.id_porteiro
             ORDER BY o.data_hora DESC, o.id_observacao DESC"
        );
        $stmt->execute([]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT o.id_observacao AS id, o.texto AS text,
                    o.data_hora AS createdAt, u.nome AS createdBy,
                    o.id_porteiro AS porterId
             FROM observacoes o
             INNER JOIN usuarios u ON u.id_usuario = o.id_porteiro
             WHERE o.data_hora >= :inicio
               AND o.data_hora < DATE_ADD(:fim, INTERVAL 1 DAY)
             ORDER BY o.data_hora DESC, o.id_observacao DESC"
        );
        $stmt->execute([':inicio' => $data, ':fim' => $data]);
    }

    responderObservacaoListar(200, ['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $erro) {
    error_log('Falha ao listar observações: ' . $erro->getMessage());
    responderObservacaoListar(500, ['ok' => false, 'message' => 'Não foi possível carregar as observações.']);
}
