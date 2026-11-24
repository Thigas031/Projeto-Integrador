<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

exigir_login();
exigir_cargo(['GERENTE', 'PORTEIRO']);

header('Content-Type: application/json; charset=utf-8');

function responderSalaListar($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderSalaListar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

try {
    $stmt = $pdo->prepare(
        "SELECT id_sala AS id, nome AS name, numero, bloco
         FROM salas
         ORDER BY bloco ASC, numero ASC, nome ASC"
    );
    $stmt->execute([]);
    responderSalaListar(200, ['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $erro) {
    error_log('Falha ao listar salas: ' . $erro->getMessage());
    responderSalaListar(500, ['ok' => false, 'message' => 'Não foi possível carregar as salas.']);
}
