<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Método não permitido.');
}

session_start();

$_SESSION = [];
$cookie = session_get_cookie_params();
if (!session_destroy()) {
    http_response_code(500);
    exit('Não foi possível encerrar a sessão.');
}

if (!setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly'])) {
    http_response_code(500);
    exit('A sessão foi encerrada, mas não foi possível limpar o cookie do navegador.');
}

header('Location: ../pages/login.html');
exit;
