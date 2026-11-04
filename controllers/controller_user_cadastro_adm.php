<?php
require_once("../config/config.php");

$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = $_POST['senha'];

$senhahost = password_hash($senha, PASSWORD_DEFAULT);

$sql = "INSERT INTO cadastros(nome_user, mail_user, senha_user)
VALUES (:nome, :email, :senha)";

$stmt = $pdo->prepare($sql);

$stmt->execute([':nome' => $nome, ':email' => $email, ':senha' => $senhahost]);

require("../views/tela_principal.php");