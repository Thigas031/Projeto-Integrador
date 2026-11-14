<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['GERENTE']);

function responderUsuarioEditar($status, $dados)
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
    responderUsuarioEditar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$nome = trim(is_string($_POST['nome'] ?? null) ? $_POST['nome'] : '');
$cargoEntrada = $_POST['cargo'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$telefoneEntrada = trim(is_string($_POST['telefone'] ?? null) ? $_POST['telefone'] : '');
$email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
$cargos = [
    'GERENTE' => 'Gerente',
    'GERENTE GERAL' => 'Gerente',
    'PORTEIRO' => 'Porteiro',
];
$cargo = is_string($cargoEntrada) ? ($cargos[strtoupper(trim($cargoEntrada))] ?? '') : '';
$telefone = $telefoneEntrada === '' ? null : $telefoneEntrada;

if ($id === false || $id === null || $id < 1 || $nome === '' || strlen($nome) > 100 ||
    !preg_match('/^\d{11}$/D', is_string($cpf) ? $cpf : '') || $cargo === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150 ||
    ($telefone !== null && (strlen($telefone) > 20 || !preg_match('/^[0-9()+. \-]+$/D', $telefone)))) {
    responderUsuarioEditar(400, ['ok' => false, 'message' => 'Dados do usuário inválidos.']);
}

try {
    $stmtCpf = $pdo->prepare(
        "SELECT id_usuario FROM usuarios
         WHERE cpf = :cpf AND id_usuario <> :id
         LIMIT 1"
    );
    $stmtCpf->execute([':cpf' => $cpf, ':id' => $id]);
    if ($stmtCpf->fetch(PDO::FETCH_ASSOC)) {
        responderUsuarioEditar(409, ['ok' => false, 'message' => 'Já existe outro usuário com esse CPF.']);
    }

    $stmtEmail = $pdo->prepare(
        "SELECT id_usuario FROM usuarios
         WHERE email = :email AND id_usuario <> :id
         LIMIT 1"
    );
    $stmtEmail->execute([':email' => $email, ':id' => $id]);
    if ($stmtEmail->fetch(PDO::FETCH_ASSOC)) {
        responderUsuarioEditar(409, ['ok' => false, 'message' => 'Já existe outro usuário com esse e-mail.']);
    }

    $stmt = $pdo->prepare(
        "UPDATE usuarios
         SET nome = :nome, cargo = :cargo, cpf = :cpf,
             telefone = :telefone, email = :email
         WHERE id_usuario = :id"
    );
    $stmt->execute([
        ':nome' => $nome,
        ':cargo' => $cargo,
        ':cpf' => $cpf,
        ':telefone' => $telefone,
        ':email' => $email,
        ':id' => $id,
    ]);
    if ($stmt->rowCount() === 0) {
        $stmtExiste = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id LIMIT 1");
        $stmtExiste->execute([':id' => $id]);
        if (!$stmtExiste->fetch(PDO::FETCH_ASSOC)) {
            responderUsuarioEditar(404, ['ok' => false, 'message' => 'Usuário não encontrado.']);
        }
    }

    responderUsuarioEditar(200, ['ok' => true, 'message' => 'Usuário atualizado com sucesso.']);
} catch (PDOException $erro) {
    error_log('Falha ao editar usuário: ' . $erro->getMessage());
    $duplicado = isset($erro->errorInfo[1]) && (int) $erro->errorInfo[1] === 1062;
    responderUsuarioEditar($duplicado ? 409 : 500, [
        'ok' => false,
        'message' => $duplicado ? 'CPF ou e-mail já cadastrado.' : 'Não foi possível atualizar o usuário.',
    ]);
}
