<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['GERENTE']);

function responderSalaEditar($status, $dados)
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
    responderSalaEditar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$nome = trim(is_string($_POST['nome'] ?? null) ? $_POST['nome'] : '');
$numero = trim(is_string($_POST['numero'] ?? null) ? $_POST['numero'] : '');
$bloco = trim(is_string($_POST['bloco'] ?? null) ? $_POST['bloco'] : '');
if ($id === false || $id === null || $id < 1 || $nome === '' || $numero === '' || $bloco === '' ||
    strlen($nome) > 100 || strlen($numero) > 20 || strlen($bloco) > 50) {
    responderSalaEditar(400, ['ok' => false, 'message' => 'Dados da sala inválidos.']);
}

try {
    $stmtDuplicada = $pdo->prepare(
        "SELECT id_sala FROM salas
         WHERE numero = :numero AND bloco = :bloco AND id_sala <> :id
         LIMIT 1"
    );
    $stmtDuplicada->execute([':numero' => $numero, ':bloco' => $bloco, ':id' => $id]);
    if ($stmtDuplicada->fetch(PDO::FETCH_ASSOC)) {
        responderSalaEditar(409, ['ok' => false, 'message' => 'Já existe uma sala com esse número e bloco.']);
    }

    $stmt = $pdo->prepare(
        "UPDATE salas SET nome = :nome, numero = :numero, bloco = :bloco
         WHERE id_sala = :id"
    );
    $stmt->execute([':nome' => $nome, ':numero' => $numero, ':bloco' => $bloco, ':id' => $id]);
    if ($stmt->rowCount() === 0) {
        $stmtExiste = $pdo->prepare("SELECT id_sala FROM salas WHERE id_sala = :id LIMIT 1");
        $stmtExiste->execute([':id' => $id]);
        if (!$stmtExiste->fetch(PDO::FETCH_ASSOC)) {
            responderSalaEditar(404, ['ok' => false, 'message' => 'Sala não encontrada.']);
        }
    }

    responderSalaEditar(200, ['ok' => true, 'message' => 'Sala atualizada com sucesso.']);
} catch (PDOException $erro) {
    error_log('Falha ao editar sala: ' . $erro->getMessage());
    responderSalaEditar(500, ['ok' => false, 'message' => 'Não foi possível atualizar a sala.']);
}
