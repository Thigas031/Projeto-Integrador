<?php
session_start();
require_once("../config/config.php");

$email = $_POST['email'];
$senha = $_POST['senha'];

$sql = "SELECT * FROM cadastros WHERE mail_user = :email";

$stmt = $pdo->prepare($sql);

$stmt->execute([':email' => $email]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if($usuario && password_verify($senha, $usuario['senha_user'])) {
    $_SESSION['id'] = $usuario['id_user'];
    $_SESSION['nome'] = $usuario['nome_user'];
    $_SESSION['email'] = $usuario['mail_user'];

    require("../views/tela_principal.php");
} else{
    $_SESSION['erro_login'] = "E-mail ou senha incorretos.";

    header("Location: ..");
    exit;
}