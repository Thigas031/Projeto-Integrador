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
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT cargo
         FROM usuarios
         WHERE id_usuario = :id AND ativo = 1
         LIMIT 1"
    );
    $stmt->execute([':id' => $_SESSION['id'] ?? 0]);
    $cargo = $stmt->fetchColumn();

    if (!is_string($cargo) || getProfilePermissions($cargo) === []) {
        $_SESSION = [];
        session_destroy();
        http_response_code(401);
        exit('Autenticação necessária.');
    }

    $_SESSION['cargo'] = strtoupper($cargo);
    $permissions = getProfilePermissions($cargo);
    $requiredPermissions = array_map(function ($cargo) {
        return strtoupper(trim($cargo));
    }, $cargos);

    if (array_intersect($permissions, $requiredPermissions) === []) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}
