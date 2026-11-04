<?php
session_start();
require_once("../config/config.php");

$usuario = $_POST['usuario'];
$senha = $_POST['senha'];

$sql = "SELECT * FROM usuario WHERE nome = :usuario";

$stmt = $pdo->prepare($sql);

$stmt->execute([':usuario' => $usuario]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if($usuario && password_verify($senha, $usuario['senha'])) {
    $_SESSION['id'] = $usuario['id_usuario'];
    $_SESSION['usuario'] = $usuario['nome'];

    header("Location: ../pages/listaschaves.html");
} else{
    $_SESSION['erro_login'] = "E-mail ou senha incorretos.";

    header("Location: ../pages/login.html");
    exit;
}