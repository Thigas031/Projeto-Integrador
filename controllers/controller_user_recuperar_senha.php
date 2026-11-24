<?php
session_start();
require_once __DIR__ . '/../config/config.php';

function responderRecuperacaoSenha($status, $mensagem)
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $status < 400, 'message' => $mensagem], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION['sucesso_login'] = $mensagem;
    header('Location: ../pages/login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderRecuperacaoSenha(405, 'Método não permitido.');
}

$cpf = $_POST['cpf'] ?? '';
$email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
$respostaNeutra = 'Se os dados corresponderem a uma conta, a senha inicial será redefinida.';
if (!is_string($cpf) || !preg_match('/^\d{11}$/D', $cpf) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderRecuperacaoSenha(200, $respostaNeutra);
}

try {
    $stmt = $pdo->prepare(
        "SELECT id_usuario
         FROM usuarios
         WHERE cpf = :cpf AND email = :email AND ativo = 1
         LIMIT 1"
    );
    $stmt->execute([':cpf' => $cpf, ':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $senhaInicial = substr($cpf, 0, 4);
        $hash = password_hash($senhaInicial, PASSWORD_DEFAULT);
        $stmtAtualizar = $pdo->prepare(
            "UPDATE usuarios SET senha = :senha WHERE id_usuario = :id"
        );
        $stmtAtualizar->execute([':senha' => $hash, ':id' => $usuario['id_usuario']]);
    }

    responderRecuperacaoSenha(200, $respostaNeutra);
} catch (PDOException $erro) {
    error_log('Falha ao redefinir senha: ' . $erro->getMessage());
    responderRecuperacaoSenha(200, $respostaNeutra);
}
