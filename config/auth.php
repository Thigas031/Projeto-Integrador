<?php
require_once __DIR__ . '/../controllers/controller_profile_permissions.php';

function exigir_login()
{
    if (empty($_SESSION['id'])) {
        header('Location: ../pages/login.html');
        exit;
    }
}

function exigir_cargo(array $cargos)
{
    $permissions = getProfilePermissions($_SESSION['cargo'] ?? '');
    $requiredPermissions = array_map(function ($cargo) {
        return strtoupper(trim($cargo));
    }, $cargos);

    if (array_intersect($permissions, $requiredPermissions) === []) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}
