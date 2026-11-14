<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['GERENTE']);

function responderChaveDesativar($status, $dados)
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION[$status < 400 ? 'sucesso_chaves' : 'erro_chaves'] = $dados['message'];
    header('Location: ../pages/listadechaves.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderChaveDesativar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$idChave = filter_var($_POST['id_chave'] ?? null, FILTER_VALIDATE_INT);
if ($idChave === false || $idChave === null || $idChave < 1) {
    responderChaveDesativar(400, ['ok' => false, 'message' => 'Identificador de chave inválido.']);
}

try {
    $pdo->beginTransaction();

    $stmtChave = $pdo->prepare(
        "SELECT status_chaves, ativo
         FROM chaves
         WHERE id_chave = :id
         FOR UPDATE"
    );
    $stmtChave->execute([':id' => $idChave]);
    $chave = $stmtChave->fetch(PDO::FETCH_ASSOC);
    if (!$chave || (int) $chave['ativo'] !== 1) {
        $pdo->rollBack();
        responderChaveDesativar(404, ['ok' => false, 'message' => 'Chave não encontrada ou já inativa.']);
    }

    $stmtAberta = $pdo->prepare(
        "SELECT id_movimentacao
         FROM movimentacoes
         WHERE id_chave = :id AND data_devolucao IS NULL
         LIMIT 1"
    );
    $stmtAberta->execute([':id' => $idChave]);
    if ($stmtAberta->fetch(PDO::FETCH_ASSOC) || $chave['status_chaves'] !== 'Disponivel') {
        $pdo->rollBack();
        responderChaveDesativar(409, ['ok' => false, 'message' => 'Não é possível desativar uma chave emprestada ou atrasada.']);
    }

    $stmt = $pdo->prepare("UPDATE chaves SET ativo = 0 WHERE id_chave = :id");
    $stmt->execute([':id' => $idChave]);
    $pdo->commit();
    responderChaveDesativar(200, [
        'ok' => true,
        'message' => 'Chave desativada com sucesso.',
        'data' => ['id' => $idChave, 'ativo' => false],
    ]);
} catch (PDOException $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Falha ao desativar chave: ' . $erro->getMessage());
    responderChaveDesativar(500, ['ok' => false, 'message' => 'Não foi possível desativar a chave.']);
}
