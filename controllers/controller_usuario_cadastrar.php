<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

exigir_login();
exigir_cargo(['GERENTE']);

function responderUsuarioCadastrar($status, $dados)
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
    responderUsuarioCadastrar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$nome = trim(is_string($_POST['nome'] ?? null) ? $_POST['nome'] : '');
$cargoEntrada = $_POST['cargo'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$telefoneEntrada = trim(is_string($_POST['telefone'] ?? null) ? $_POST['telefone'] : '');
$email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
$cargos = [
    'GERENTE' => 'Gerente',
    'GERENTE GERAL' => 'Gerente',
    'PORTEIRO' => 'Porteiro',
    'PROFESSOR' => 'Professor',
    'COORDENACAO' => 'Coordenacao',
    'COORDENAÇÃO' => 'Coordenacao',
    'FUNCIONARIO' => 'Funcionario',
    'FUNCIONÁRIO' => 'Funcionario',
];
$cargo = is_string($cargoEntrada) ? ($cargos[strtoupper(trim($cargoEntrada))] ?? '') : '';
$telefone = $telefoneEntrada === '' ? null : $telefoneEntrada;

if ($nome === '' || strlen($nome) > 100 || !preg_match('/^\d{11}$/D', is_string($cpf) ? $cpf : '') ||
    $cargo === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150 ||
    ($telefone !== null && (strlen($telefone) > 20 || !preg_match('/^[0-9()+. \-]+$/D', $telefone)))) {
    responderUsuarioCadastrar(400, ['ok' => false, 'message' => 'Confira nome, cargo, CPF, telefone e e-mail.']);
}

try {
    $stmtCpf = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE cpf = :cpf LIMIT 1");
    $stmtCpf->execute([':cpf' => $cpf]);
    if ($stmtCpf->fetch(PDO::FETCH_ASSOC)) {
        responderUsuarioCadastrar(409, ['ok' => false, 'message' => 'Já existe um usuário com esse CPF.']);
    }

    $stmtEmail = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1");
    $stmtEmail->execute([':email' => $email]);
    if ($stmtEmail->fetch(PDO::FETCH_ASSOC)) {
        responderUsuarioCadastrar(409, ['ok' => false, 'message' => 'Já existe um usuário com esse e-mail.']);
    }

    $senhaInicial = substr($cpf, 0, 4);
    $hash = password_hash($senhaInicial, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO usuarios (nome, cargo, cpf, telefone, email, senha, ativo)
         VALUES (:nome, :cargo, :cpf, :telefone, :email, :senha, 1)"
    );
    $stmt->execute([
        ':nome' => $nome,
        ':cargo' => $cargo,
        ':cpf' => $cpf,
        ':telefone' => $telefone,
        ':email' => $email,
        ':senha' => $hash,
    ]);

    responderUsuarioCadastrar(201, [
        'ok' => true,
        'message' => 'Usuário cadastrado com sucesso.',
        'data' => ['id' => (int) $pdo->lastInsertId()],
    ]);
} catch (PDOException $erro) {
    error_log('Falha ao cadastrar usuário: ' . $erro->getMessage());
    $duplicado = isset($erro->errorInfo[1]) && (int) $erro->errorInfo[1] === 1062;
    responderUsuarioCadastrar($duplicado ? 409 : 500, [
        'ok' => false,
        'message' => $duplicado ? 'CPF ou e-mail já cadastrado.' : 'Não foi possível cadastrar o usuário.',
    ]);
}
