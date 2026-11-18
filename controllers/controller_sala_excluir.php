<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['GERENTE']);

function responderSalaExcluir($status, $dados)
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION[$status < 400 ? 'sucesso_salas' : 'erro_salas'] = $dados['message'];
    header('Location: ../pages/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderSalaExcluir(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    responderSalaExcluir(400, ['ok' => false, 'message' => 'Identificador de sala inválido.']);
}

try {
    $stmtChaves = $pdo->prepare("SELECT id_chave FROM chaves WHERE id_sala = :id LIMIT 1");
    $stmtChaves->execute([':id' => $id]);
    if ($stmtChaves->fetch(PDO::FETCH_ASSOC)) {
        responderSalaExcluir(409, ['ok' => false, 'message' => 'Não é possível excluir uma sala que possui chaves vinculadas.']);
    }

    $stmt = $pdo->prepare("DELETE FROM salas WHERE id_sala = :id");
    $stmt->execute([':id' => $id]);
    if ($stmt->rowCount() === 0) {
        responderSalaExcluir(404, ['ok' => false, 'message' => 'Sala não encontrada.']);
    }

    responderSalaExcluir(200, ['ok' => true, 'message' => 'Sala excluída com sucesso.']);
} catch (PDOException $erro) {
    error_log('Falha ao excluir sala: ' . $erro->getMessage());
    $chavesVinculadas = isset($erro->errorInfo[1]) && (int) $erro->errorInfo[1] === 1451;
    responderSalaExcluir($chavesVinculadas ? 409 : 500, [
        'ok' => false,
        'message' => $chavesVinculadas
            ? 'Não é possível excluir uma sala que possui chaves vinculadas.'
            : 'Não foi possível excluir a sala.',
    ]);
}
