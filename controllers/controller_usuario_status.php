<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['GERENTE']);

function responderUsuarioStatus($status, $dados)
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION[$status < 400 ? 'sucesso_usuarios' : 'erro_usuarios'] = $dados['message'];
    header('Location: ../pages/cadastrodeusuario.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderUsuarioStatus(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$ativoEntrada = $_POST['ativo'] ?? null;
if ($id === false || $id === null || $id < 1 || !is_string($ativoEntrada) ||
    !in_array($ativoEntrada, ['0', '1'], true)) {
    responderUsuarioStatus(400, ['ok' => false, 'message' => 'Identificador ou status inválido.']);
}

$ativo = (int) $ativoEntrada;
if ($ativo === 0 && (int) $id === (int) $_SESSION['id']) {
    responderUsuarioStatus(409, ['ok' => false, 'message' => 'Não é permitido desativar o próprio usuário.']);
}

try {
    $stmt = $pdo->prepare("UPDATE usuarios SET ativo = :ativo WHERE id_usuario = :id");
    $stmt->execute([':ativo' => $ativo, ':id' => $id]);

    if ($stmt->rowCount() === 0) {
        $stmtExiste = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id LIMIT 1");
        $stmtExiste->execute([':id' => $id]);
        if (!$stmtExiste->fetch(PDO::FETCH_ASSOC)) {
            responderUsuarioStatus(404, ['ok' => false, 'message' => 'Usuário não encontrado.']);
        }
    }

    responderUsuarioStatus(200, [
        'ok' => true,
        'message' => $ativo ? 'Usuário ativado com sucesso.' : 'Usuário desativado com sucesso.',
    ]);
} catch (PDOException $erro) {
    error_log('Falha ao alterar status do usuário: ' . $erro->getMessage());
    responderUsuarioStatus(500, ['ok' => false, 'message' => 'Não foi possível alterar o status do usuário.']);
}
