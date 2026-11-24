<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

exigir_login();
exigir_cargo(['PORTEIRO']);

function responderMovimentacaoRetirar($status, $dados)
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION[$status < 400 ? 'sucesso_retirada' : 'erro_retirada'] = $dados['message'];
    header('Location: ../pages/retiradadechaves.php');
    exit;
}

function criarDataPrevista($data, $hora, $agora)
{
    if (!is_string($data) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $data) ||
        !is_string($hora) || !preg_match('/^\d{2}:\d{2}$/D', $hora) ||
        !is_string($agora) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $agora)) {
        return false;
    }

    $partesData = array_map('intval', explode('-', $data));
    $partesHora = array_map('intval', explode(':', $hora));
    if (!checkdate($partesData[1], $partesData[2], $partesData[0]) ||
        $partesHora[0] > 23 || $partesHora[1] > 59) {
        return false;
    }

    $prevista = $data . ' ' . $hora . ':00';
    return $prevista > $agora ? $prevista : false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderMovimentacaoRetirar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$idSala = filter_var($_POST['sala'] ?? null, FILTER_VALIDATE_INT);
$idUsuarioEntrada = $_POST['usuario'] ?? $_POST['id_usuario'] ?? '';
$idUsuario = null;
if (!is_string($idUsuarioEntrada)) {
    responderMovimentacaoRetirar(400, ['ok' => false, 'message' => 'Identificação da pessoa responsável inválida.']);
}
$idUsuarioEntrada = trim($idUsuarioEntrada);
if ($idUsuarioEntrada !== '') {
    $idUsuario = filter_var($idUsuarioEntrada, FILTER_VALIDATE_INT);
}
$idChave = filter_var($_POST['chave'] ?? $_POST['id_chave'] ?? null, FILTER_VALIDATE_INT);
$nomeRetiranteEntrada = $_POST['nome_retirante'] ?? '';
$cargoRetiranteEntrada = $_POST['cargo_retirante'] ?? '';
$observacaoEntrada = $_POST['observacao'] ?? '';
$observacao = is_string($observacaoEntrada) ? trim($observacaoEntrada) : '';

if ($idSala === false || $idSala === null || $idSala < 1 ||
    ($idUsuario !== null && ($idUsuario === false || $idUsuario < 1)) ||
    $idChave === false || $idChave === null || $idChave < 1 ||
    !is_string($observacaoEntrada) || strlen($observacao) > 500) {
    responderMovimentacaoRetirar(400, ['ok' => false, 'message' => 'Selecione uma sala, uma pessoa responsável válida e uma chave disponível.']);
}

if (!is_string($nomeRetiranteEntrada) || !is_string($cargoRetiranteEntrada)) {
    responderMovimentacaoRetirar(400, ['ok' => false, 'message' => 'Informe um nome e um cargo válidos para a pessoa responsável.']);
}
$nomeRetiranteEntrada = trim($nomeRetiranteEntrada);
$cargoRetiranteEntrada = trim($cargoRetiranteEntrada);
$comprimentoTexto = function ($texto) {
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
};
if ($idUsuario === null &&
    ($nomeRetiranteEntrada === '' || $cargoRetiranteEntrada === '' ||
        $comprimentoTexto($nomeRetiranteEntrada) > 100 || $comprimentoTexto($cargoRetiranteEntrada) > 100)) {
    responderMovimentacaoRetirar(400, ['ok' => false, 'message' => 'Para pessoas não cadastradas, informe nome e cargo com até 100 caracteres.']);
}

try {
    $stmtAgora = $pdo->prepare("SELECT NOW()");
    $stmtAgora->execute([]);
    $agora = $stmtAgora->fetchColumn();
    $dataPrevista = criarDataPrevista(
        $_POST['data_prevista'] ?? '',
        $_POST['hora_prevista'] ?? '',
        $agora
    );
    if ($dataPrevista === false) {
        responderMovimentacaoRetirar(400, [
            'ok' => false,
            'message' => 'Informe uma data e hora futuras válidas para a devolução.',
        ]);
    }

    $pdo->beginTransaction();

    $stmtChave = $pdo->prepare(
        "SELECT id_sala, status_chaves, ativo
         FROM chaves
         WHERE id_chave = :id
         FOR UPDATE"
    );
    $stmtChave->execute([':id' => $idChave]);
    $chave = $stmtChave->fetch(PDO::FETCH_ASSOC);

    if (!$chave || (int) $chave['ativo'] !== 1 || (int) $chave['id_sala'] !== $idSala) {
        $pdo->rollBack();
        responderMovimentacaoRetirar(404, ['ok' => false, 'message' => 'A chave não pertence à sala selecionada ou está inativa.']);
    }

    $stmtAberta = $pdo->prepare(
        "SELECT id_movimentacao
         FROM movimentacoes
         WHERE id_chave = :id AND data_devolucao IS NULL
         LIMIT 1"
    );
    $stmtAberta->execute([':id' => $idChave]);
    if ($chave['status_chaves'] !== 'Disponivel' || $stmtAberta->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        responderMovimentacaoRetirar(409, ['ok' => false, 'message' => 'A chave não está disponível para retirada.']);
    }

    if ($idUsuario !== null) {
        $stmtUsuario = $pdo->prepare(
            "SELECT nome, cargo
             FROM usuarios
             WHERE id_usuario = :id AND ativo = 1
             LIMIT 1"
        );
        $stmtUsuario->execute([':id' => $idUsuario]);
        $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
        if (!$usuario) {
            $pdo->rollBack();
            responderMovimentacaoRetirar(400, ['ok' => false, 'message' => 'O usuário selecionado não está ativo.']);
        }
        $nomeRetirante = $usuario['nome'];
        $cargoRetirante = $usuario['cargo'];
    } else {
        $nomeRetirante = $nomeRetiranteEntrada;
        $cargoRetirante = $cargoRetiranteEntrada;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO movimentacoes
            (id_chave, id_usuario, nome_retirante, cargo_retirante, id_porteiro,
             data_retirada, data_prevista, observacao)
         VALUES
            (:chave, :usuario, :nome_retirante, :cargo_retirante, :porteiro,
             NOW(), :prevista, :observacao)"
    );
    $stmt->bindValue(':chave', $idChave, PDO::PARAM_INT);
    $stmt->bindValue(':usuario', $idUsuario, $idUsuario === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':nome_retirante', $nomeRetirante, PDO::PARAM_STR);
    $stmt->bindValue(':cargo_retirante', $cargoRetirante, PDO::PARAM_STR);
    $stmt->bindValue(':porteiro', $_SESSION['id'], PDO::PARAM_INT);
    $stmt->bindValue(':prevista', $dataPrevista, PDO::PARAM_STR);
    $stmt->bindValue(':observacao', $observacao === '' ? null : $observacao, $observacao === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->execute();
    $idMovimentacao = (int) $pdo->lastInsertId();

    $stmtAtualizar = $pdo->prepare(
        "UPDATE chaves
         SET status_chaves = 'Emprestada', responsavel = :usuario
         WHERE id_chave = :chave"
    );
    $stmtAtualizar->bindValue(':usuario', $idUsuario, $idUsuario === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmtAtualizar->bindValue(':chave', $idChave, PDO::PARAM_INT);
    $stmtAtualizar->execute();
    $pdo->commit();

    responderMovimentacaoRetirar(201, [
        'ok' => true,
        'message' => 'Retirada registrada com sucesso.',
        'data' => ['id' => $idMovimentacao],
    ]);
} catch (PDOException $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Falha ao registrar retirada: ' . $erro->getMessage());
    responderMovimentacaoRetirar(500, ['ok' => false, 'message' => 'Não foi possível registrar a retirada.']);
}
