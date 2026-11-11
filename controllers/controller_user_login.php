<?php
ini_set('session.use_strict_mode', '1');
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function responderLogin($status, $payload)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    responderLogin(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SESSION['id'])) {
    responderLogin(401, ['ok' => false, 'message' => 'Autenticação necessária.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'logout') {
    $_SESSION = [];
    if (!session_destroy()) {
        responderLogin(500, ['ok' => false, 'message' => 'Não foi possível encerrar a sessão.']);
    }

    $cookie = session_get_cookie_params();
    if (!setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly'])) {
        responderLogin(500, ['ok' => false, 'message' => 'A sessão foi encerrada, mas não foi possível limpar o cookie do navegador.']);
    }
    responderLogin(200, ['ok' => true, 'message' => 'Sessão encerrada.']);
}

require_once("../config/config.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_SESSION['id'] ?? null;
    if (!$userId) {
        responderLogin(401, ['ok' => false, 'message' => 'Autenticação necessária.']);
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT id_usuario, nome, cpf, email, cargo
             FROM usuarios
             WHERE id_usuario = :id AND ativo = 1
             LIMIT 1"
        );
        $stmt->execute([':id' => $userId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        $perfil = $usuario ? strtoupper($usuario['cargo']) : '';

        if (!$usuario || !in_array($perfil, ['GERENTE', 'PORTEIRO'], true)) {
            $_SESSION = [];
            session_destroy();
            responderLogin(401, ['ok' => false, 'message' => 'Sessão inválida ou expirada.']);
        }

        $_SESSION['usuario'] = $usuario['nome'];
        $_SESSION['cargo'] = $perfil;
        responderLogin(200, [
            'ok' => true,
            'user' => [
                'id' => $usuario['id_usuario'],
                'name' => $usuario['nome'],
                'cpf' => $usuario['cpf'],
                'email' => $usuario['email'],
                'role' => $perfil,
            ],
        ]);
    } catch (PDOException $erro) {
        error_log('Falha ao validar sessão: ' . $erro->getMessage());
        responderLogin(500, ['ok' => false, 'message' => 'Não foi possível validar a sessão neste momento.']);
    }
}

$identificador = trim($_POST['identificador'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($identificador === '' || $senha === '') {
    responderLogin(400, ['ok' => false, 'message' => 'Informe CPF ou e-mail e senha.']);
}

$cpf = preg_replace('/\D/', '', $identificador);
$cpf = strlen($cpf) === 11 && preg_match('/^[\d.\-\s]+$/', $identificador) ? $cpf : '';
$email = filter_var($identificador, FILTER_VALIDATE_EMAIL) ? strtolower($identificador) : '';

if ($cpf === '' && $email === '') {
    responderLogin(401, ['ok' => false, 'message' => 'CPF/e-mail ou senha incorretos.']);
}

try {
    $sql = "SELECT id_usuario, nome, cpf, email, cargo, senha
            FROM usuarios
            WHERE ativo = 1 AND (cpf = :cpf OR email = :email)
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cpf' => $cpf, ':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $perfil = $usuario ? strtoupper($usuario['cargo']) : '';
    if (!$usuario || !password_verify($senha, $usuario['senha']) ||
        !in_array($perfil, ['GERENTE', 'PORTEIRO'], true)) {
        responderLogin(401, ['ok' => false, 'message' => 'CPF/e-mail ou senha incorretos.']);
    }

    session_regenerate_id(true);
    $_SESSION['id'] = $usuario['id_usuario'];
    $_SESSION['usuario'] = $usuario['nome'];
    $_SESSION['cargo'] = $perfil;

    responderLogin(200, [
        'ok' => true,
        'user' => [
            'id' => $usuario['id_usuario'],
            'name' => $usuario['nome'],
            'cpf' => $usuario['cpf'],
            'email' => $usuario['email'],
            'role' => $perfil
        ]
    ]);
} catch (PDOException $erro) {
    error_log('Falha ao autenticar usuário: ' . $erro->getMessage());
    responderLogin(500, ['ok' => false, 'message' => 'Não foi possível autenticar neste momento.']);
}
