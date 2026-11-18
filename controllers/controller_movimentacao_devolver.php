<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['PORTEIRO']);

function responderMovimentacaoDevolver($status, $dados)
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION[$status < 400 ? 'sucesso_devolucao' : 'erro_devolucao'] = $dados['message'];
    header('Location: ../pages/devolucaodechaves.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderMovimentacaoDevolver(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$idChave = filter_var($_POST['chave'] ?? $_POST['id_chave'] ?? null, FILTER_VALIDATE_INT);
if ($idChave === false || $idChave === null || $idChave < 1) {
    responderMovimentacaoDevolver(400, ['ok' => false, 'message' => 'Identificador de chave inválido.']);
}

try {
    $pdo->beginTransaction();

    $stmtChave = $pdo->prepare(
        "SELECT id_chave, ativo
         FROM chaves
         WHERE id_chave = :id
         FOR UPDATE"
    );
    $stmtChave->execute([':id' => $idChave]);
    $chave = $stmtChave->fetch(PDO::FETCH_ASSOC);
    if (!$chave) {
        $pdo->rollBack();
        responderMovimentacaoDevolver(404, ['ok' => false, 'message' => 'Chave não encontrada.']);
    }

    $stmtMovimentacao = $pdo->prepare(
        "SELECT id_movimentacao
         FROM movimentacoes
         WHERE id_chave = :id AND data_devolucao IS NULL
         ORDER BY id_movimentacao DESC
         LIMIT 1
         FOR UPDATE"
    );
    $stmtMovimentacao->execute([':id' => $idChave]);
    $movimentacao = $stmtMovimentacao->fetch(PDO::FETCH_ASSOC);
    if (!$movimentacao) {
        $pdo->rollBack();
        responderMovimentacaoDevolver(409, ['ok' => false, 'message' => 'Não há retirada em aberto para esta chave.']);
    }

    $stmtAtualizarMovimentacao = $pdo->prepare(
        "UPDATE movimentacoes
         SET data_devolucao = NOW()
         WHERE id_movimentacao = :id AND data_devolucao IS NULL"
    );
    $stmtAtualizarMovimentacao->execute([':id' => $movimentacao['id_movimentacao']]);
    $stmtAtualizarChave = $pdo->prepare(
        "UPDATE chaves
         SET status_chaves = 'Disponivel', responsavel = NULL
         WHERE id_chave = :id"
    );
    $stmtAtualizarChave->execute([':id' => $idChave]);
    $pdo->commit();

    responderMovimentacaoDevolver(200, [
        'ok' => true,
        'message' => 'Devolução registrada com sucesso.',
        'data' => ['status' => 'Devolvida'],
    ]);
} catch (PDOException $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Falha ao registrar devolução: ' . $erro->getMessage());
    responderMovimentacaoDevolver(500, ['ok' => false, 'message' => 'Não foi possível registrar a devolução.']);
}
