<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['GERENTE', 'PORTEIRO']);

function responderChaveCadastrar($status, $dados)
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tipo = $status < 400 ? 'sucesso_chaves' : 'erro_chaves';
    $_SESSION[$tipo] = $dados['message'];
    header('Location: ../pages/listadechaves.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderChaveCadastrar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$idSala = filter_var($_POST['sala'] ?? null, FILTER_VALIDATE_INT);
$nome = trim(is_string($_POST['nome_chave'] ?? null) ? $_POST['nome_chave'] : '');
$numero = trim(is_string($_POST['numero_chave'] ?? null) ? $_POST['numero_chave'] : '');

if ($idSala === false || $idSala === null || $idSala < 1 || $nome === '' ||
    mb_strlen($nome, 'UTF-8') > 100 || $numero === '' || strlen($numero) > 30) {
    responderChaveCadastrar(400, ['ok' => false, 'message' => 'Informe nome, sala e número válidos para a chave.']);
}

try {
    $stmtSala = $pdo->prepare("SELECT id_sala FROM salas WHERE id_sala = :id LIMIT 1");
    $stmtSala->execute([':id' => $idSala]);
    if (!$stmtSala->fetch(PDO::FETCH_ASSOC)) {
        responderChaveCadastrar(400, ['ok' => false, 'message' => 'A sala selecionada não existe.']);
    }

    $stmtDuplicada = $pdo->prepare("SELECT id_chave FROM chaves WHERE numero_chave = :numero LIMIT 1");
    $stmtDuplicada->execute([':numero' => $numero]);
    if ($stmtDuplicada->fetch(PDO::FETCH_ASSOC)) {
        responderChaveCadastrar(409, ['ok' => false, 'message' => 'Já existe uma chave com esse número.']);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO chaves (id_sala, nome_chave, numero_chave, status_chaves, responsavel, ativo)
         VALUES (:sala, :nome, :numero, 'Disponivel', NULL, 1)"
    );
    $stmt->execute([':sala' => $idSala, ':nome' => $nome, ':numero' => $numero]);
    responderChaveCadastrar(201, [
        'ok' => true,
        'message' => 'Chave cadastrada com sucesso.',
        'data' => ['id' => (int) $pdo->lastInsertId()],
    ]);
} catch (PDOException $erro) {
    error_log('Falha ao cadastrar chave: ' . $erro->getMessage());
    $duplicada = isset($erro->errorInfo[1]) && (int) $erro->errorInfo[1] === 1062;
    responderChaveCadastrar($duplicada ? 409 : 500, [
        'ok' => false,
        'message' => $duplicada ? 'Já existe uma chave com esse número.' : 'Não foi possível cadastrar a chave.',
    ]);
}
