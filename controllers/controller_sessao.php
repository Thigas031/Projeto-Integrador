<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

exigir_login();
exigir_cargo(['GERENTE', 'PORTEIRO']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Método não permitido.');
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

$mensagens = [];
foreach ($_SESSION as $chave => $valor) {
    if (strpos($chave, 'erro_') === 0 || strpos($chave, 'sucesso_') === 0) {
        $mensagens[$chave] = $valor;
        unset($_SESSION[$chave]);
    }
}

echo json_encode([
    'nome' => $_SESSION['nome'] ?? $_SESSION['usuario'] ?? '',
    'cargo' => $_SESSION['cargo'],
    'mensagens' => (object) $mensagens,
], JSON_UNESCAPED_UNICODE);
exit;
