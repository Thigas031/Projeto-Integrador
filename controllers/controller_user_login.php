<?php
ini_set('session.use_strict_mode', '1');
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$lembrar = isset($_POST['lembrar']) && is_string($_POST['lembrar']) &&
    in_array($_POST['lembrar'], ['1', 'on', 'true'], true);
session_set_cookie_params([
    'lifetime' => $lembrar ? 604800 : 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once("../config/config.php");

header('Cache-Control: no-store, private');

function responderLogin($status, $payload)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function retornarLogin($status, $mensagem, $dados = [])
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        responderLogin($status, array_merge(['ok' => $status < 400, 'message' => $mensagem], $dados));
    }

    header('Location: ' . ($status < 400 ? '../pages/dashboard.php' : '../pages/login.html'));
    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    responderLogin(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_SESSION['id'])) {
        responderLogin(401, ['ok' => false, 'message' => 'Autenticação necessária.']);
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT id_usuario, nome, cpf, email, cargo
             FROM usuarios
             WHERE id_usuario = :id AND ativo = 1
             LIMIT 1"
        );
        $stmt->execute([':id' => $_SESSION['id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        $perfil = $usuario ? strtoupper($usuario['cargo']) : '';

        if (!$usuario || !in_array($perfil, ['GERENTE', 'PORTEIRO'], true)) {
            $_SESSION = [];
            session_destroy();
            responderLogin(401, ['ok' => false, 'message' => 'Sessão inválida ou expirada.']);
        }

        $_SESSION['nome'] = $usuario['nome'];
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

if (($_POST['acao'] ?? '') === 'logout') {
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

$cpf = $_POST['cpf'] ?? $_POST['identificador'] ?? '';
$senha = $_POST['senha'] ?? '';

if (!is_string($cpf) || !preg_match('/^\d{11}$/D', $cpf) || !is_string($senha) || $senha === '') {
    $_SESSION['erro_login'] = 'CPF ou senha incorretos.';
    retornarLogin(401, 'CPF ou senha incorretos.');
}

try {
    $stmt = $pdo->prepare(
        "SELECT id_usuario, nome, cpf, cargo, senha
         FROM usuarios
         WHERE cpf = :cpf AND ativo = 1
         LIMIT 1"
    );
    $stmt->execute([':cpf' => $cpf]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    $perfil = $usuario ? strtoupper($usuario['cargo']) : '';

    if (!$usuario || !password_verify($senha, $usuario['senha']) ||
        !in_array($perfil, ['GERENTE', 'PORTEIRO'], true)) {
        $_SESSION['erro_login'] = 'CPF ou senha incorretos.';
        retornarLogin(401, 'CPF ou senha incorretos.');
    }

    session_regenerate_id(true);
    $_SESSION = [];
    $_SESSION['id'] = $usuario['id_usuario'];
    $_SESSION['nome'] = $usuario['nome'];
    $_SESSION['usuario'] = $usuario['nome'];
    $_SESSION['cargo'] = $perfil;
    unset($_SESSION['erro_login']);

    retornarLogin(200, 'Login realizado com sucesso.', [
        'user' => [
            'id' => $usuario['id_usuario'],
            'name' => $usuario['nome'],
            'role' => $perfil,
        ],
    ]);
} catch (PDOException $erro) {
    error_log('Falha ao autenticar usuário: ' . $erro->getMessage());
    $_SESSION['erro_login'] = 'Não foi possível autenticar neste momento.';
    retornarLogin(500, 'Não foi possível autenticar neste momento.');
}
