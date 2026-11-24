<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

exigir_login();
exigir_cargo(['GERENTE']);

header('Content-Type: application/json; charset=utf-8');

function responderUsuarioListar($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderUsuarioListar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

try {
    $stmt = $pdo->prepare(
        "SELECT id_usuario AS id, nome AS name, cpf, cargo AS role,
                telefone AS phone, email, ativo AS active
         FROM usuarios
        WHERE cargo <> 'Outro'
         ORDER BY nome ASC"
    );
    $stmt->execute([]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($usuarios as &$usuario) {
        $usuario['active'] = (bool) $usuario['active'];
        $usuario['role'] = strtoupper($usuario['role']);
    }
    unset($usuario);

    responderUsuarioListar(200, ['ok' => true, 'data' => $usuarios]);
} catch (PDOException $erro) {
    error_log('Falha ao listar usuários: ' . $erro->getMessage());
    responderUsuarioListar(500, ['ok' => false, 'message' => 'Não foi possível carregar os usuários.']);
}
