<?php

require_once __DIR__ . '/controller_profile_permissions.php';

function requirePageAccess(array $allowedProfiles)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $userId = $_SESSION['id'] ?? null;
    if (!$userId) {
        header('Location: login.html');
        exit;
    }

    require_once __DIR__ . '/../config/config.php';

    try {
        $stmt = $pdo->prepare(
            "SELECT id_usuario, nome, cargo
             FROM usuarios
             WHERE id_usuario = :id AND ativo = 1
             LIMIT 1"
        );
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $role = $user ? strtoupper($user['cargo']) : '';
        $profilePermissions = getProfilePermissions($role);

        if (!$user || $profilePermissions === []) {
            $_SESSION = [];
            session_destroy();
            header('Location: login.html');
            exit;
        }

        $_SESSION['usuario'] = $user['nome'];
        $_SESSION['cargo'] = $role;

        $requiredPermissions = array_map('strtoupper', $allowedProfiles);
        if (!array_intersect($profilePermissions, $requiredPermissions)) {
            header('Location: dashboard.php');
            exit;
        }
    } catch (PDOException $error) {
        error_log('Falha ao validar acesso à página: ' . $error->getMessage());
        http_response_code(503);
        exit('Não foi possível validar sua sessão. Tente novamente mais tarde.');
    }
}
