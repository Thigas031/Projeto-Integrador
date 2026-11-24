<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

exigir_login();
exigir_cargo(['GERENTE']);

function responderSalaCadastrar($status, $dados)
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
    responderSalaCadastrar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$nome = trim(is_string($_POST['nome'] ?? null) ? $_POST['nome'] : '');
$numero = trim(is_string($_POST['numero'] ?? null) ? $_POST['numero'] : '');
$bloco = trim(is_string($_POST['bloco'] ?? null) ? $_POST['bloco'] : '');
if ($nome === '' || $numero === '' || $bloco === '' ||
    mb_strlen($nome, 'UTF-8') > 100 || mb_strlen($numero, 'UTF-8') > 20 ||
    mb_strlen($bloco, 'UTF-8') > 50) {
    responderSalaCadastrar(400, ['ok' => false, 'message' => 'Informe nome, número e bloco válidos para a sala.']);
}

try {
    $stmtDuplicada = $pdo->prepare(
        "SELECT id_sala FROM salas
         WHERE numero = :numero AND bloco = :bloco
         LIMIT 1"
    );
    $stmtDuplicada->execute([':numero' => $numero, ':bloco' => $bloco]);
    if ($stmtDuplicada->fetch(PDO::FETCH_ASSOC)) {
        responderSalaCadastrar(409, ['ok' => false, 'message' => 'Já existe uma sala com esse número e bloco.']);
    }

    $stmt = $pdo->prepare("INSERT INTO salas (nome, numero, bloco) VALUES (:nome, :numero, :bloco)");
    $stmt->execute([':nome' => $nome, ':numero' => $numero, ':bloco' => $bloco]);
    responderSalaCadastrar(201, [
        'ok' => true,
        'message' => 'Sala cadastrada com sucesso.',
        'data' => ['id' => (int) $pdo->lastInsertId()],
    ]);
} catch (PDOException $erro) {
    error_log('Falha ao cadastrar sala: ' . $erro->getMessage());
    $duplicada = isset($erro->errorInfo[1]) && (int) $erro->errorInfo[1] === 1062;
    responderSalaCadastrar($duplicada ? 409 : 500, [
        'ok' => false,
        'message' => $duplicada
            ? 'Já existe uma sala com esse número e bloco.'
            : 'Não foi possível cadastrar a sala.',
    ]);
}
