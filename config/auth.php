<?php

function exigir_login()
{
    if (empty($_SESSION['id'])) {
        header('Location: ../pages/login.html');
        exit;
    }
}

function exigir_cargo(array $cargos)
{
    if (!in_array($_SESSION['cargo'] ?? '', $cargos, true)) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}
