<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['GERENTE']);

function responderChaveEditar($status, $dados)
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
    responderChaveEditar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$idChave = filter_var($_POST['id_chave'] ?? null, FILTER_VALIDATE_INT);
$idSala = filter_var($_POST['sala'] ?? null, FILTER_VALIDATE_INT);
$nome = trim(is_string($_POST['nome_chave'] ?? null) ? $_POST['nome_chave'] : '');
$numero = trim(is_string($_POST['numero_chave'] ?? null) ? $_POST['numero_chave'] : '');

if ($idChave === false || $idChave === null || $idChave < 1 ||
    $idSala === false || $idSala === null || $idSala < 1 || $nome === '' ||
    mb_strlen($nome, 'UTF-8') > 100 || $numero === '' || strlen($numero) > 30) {
    responderChaveEditar(400, ['ok' => false, 'message' => 'Dados da chave inválidos.']);
}

try {
    $stmtSala = $pdo->prepare("SELECT id_sala FROM salas WHERE id_sala = :id LIMIT 1");
    $stmtSala->execute([':id' => $idSala]);
    if (!$stmtSala->fetch(PDO::FETCH_ASSOC)) {
        responderChaveEditar(400, ['ok' => false, 'message' => 'A sala selecionada não existe.']);
    }

    $stmtDuplicada = $pdo->prepare(
        "SELECT id_chave FROM chaves
         WHERE numero_chave = :numero AND id_chave <> :id
         LIMIT 1"
    );
    $stmtDuplicada->execute([':numero' => $numero, ':id' => $idChave]);
    if ($stmtDuplicada->fetch(PDO::FETCH_ASSOC)) {
        responderChaveEditar(409, ['ok' => false, 'message' => 'Já existe uma chave com esse número.']);
    }

    $stmt = $pdo->prepare(
        "UPDATE chaves
         SET id_sala = :sala, nome_chave = :nome, numero_chave = :numero
         WHERE id_chave = :id AND ativo = 1"
    );
    $stmt->execute([':sala' => $idSala, ':nome' => $nome, ':numero' => $numero, ':id' => $idChave]);
    if ($stmt->rowCount() === 0) {
        $stmtExiste = $pdo->prepare("SELECT id_chave FROM chaves WHERE id_chave = :id AND ativo = 1 LIMIT 1");
        $stmtExiste->execute([':id' => $idChave]);
        if (!$stmtExiste->fetch(PDO::FETCH_ASSOC)) {
            responderChaveEditar(404, ['ok' => false, 'message' => 'Chave não encontrada ou inativa.']);
        }
    }

    responderChaveEditar(200, ['ok' => true, 'message' => 'Chave atualizada com sucesso.']);
} catch (PDOException $erro) {
    error_log('Falha ao editar chave: ' . $erro->getMessage());
    $duplicada = isset($erro->errorInfo[1]) && (int) $erro->errorInfo[1] === 1062;
    responderChaveEditar($duplicada ? 409 : 500, [
        'ok' => false,
        'message' => $duplicada ? 'Já existe uma chave com esse número.' : 'Não foi possível atualizar a chave.',
    ]);
}
