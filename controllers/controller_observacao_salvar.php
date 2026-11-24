<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

exigir_login();
exigir_cargo(['PORTEIRO']);

function responderObservacaoSalvar($status, $dados)
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION[$status < 400 ? 'sucesso_observacoes' : 'erro_observacoes'] = $dados['message'];
    header('Location: ../pages/ocorrencias.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderObservacaoSalvar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$texto = trim(is_string($_POST['texto'] ?? null) ? $_POST['texto'] : '');
if ($texto === '' || strlen($texto) > 2000) {
    responderObservacaoSalvar(400, ['ok' => false, 'message' => 'A observação deve ter entre 1 e 2000 caracteres.']);
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO observacoes (id_porteiro, texto)
         VALUES (:porteiro, :texto)"
    );
    $stmt->execute([':porteiro' => $_SESSION['id'], ':texto' => $texto]);
    responderObservacaoSalvar(201, [
        'ok' => true,
        'message' => 'Observação registrada com sucesso.',
        'data' => ['id' => (int) $pdo->lastInsertId()],
    ]);
} catch (PDOException $erro) {
    error_log('Falha ao salvar observação: ' . $erro->getMessage());
    responderObservacaoSalvar(500, ['ok' => false, 'message' => 'Não foi possível registrar a observação.']);
}
