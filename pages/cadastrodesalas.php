<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['GERENTE']); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Sala</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/menu-lateral.css">
    <link rel="stylesheet" href="../css/cadastrodeusuario.css">
    <link rel="stylesheet" href="../css/cadastrodesalas.css">
</head>
<body data-profiles="GERENTE" data-auth-state="pending">
    <div id="menu-lateral-container" data-page="cadastro-salas" data-menu-url="menu-lateral.html"></div>

    <main class="app-page">
        <div class="user-wrap">
            <header class="user-header room-header">
                <img class="brand-logo" src="../assets/icons/Logo%20sistema%20de%20chaves.svg" alt="Sistema de gerenciamento de chaves">
                <h1 id="room-form-title">Cadastro de Sala</h1>
                <span class="room-icon" aria-hidden="true"><img src="../assets/icons/Casa.svg" alt=""></span>
            </header>

            <form id="room-form" class="user-form" novalidate>
                <p id="room-feedback" class="form-feedback" role="status" aria-live="polite"></p>
                <p id="room-edit-context" class="room-edit-context" hidden></p>

                <div class="field">
                    <label for="room-name">Nome da sala</label>
                    <input id="room-name" name="nome" type="text" maxlength="100" placeholder="Ex.: Laboratório de Informática" autocomplete="off" required>
                </div>

                <div class="field">
                    <label for="room-number">Número da sala</label>
                    <input id="room-number" name="numero" type="text" maxlength="20" placeholder="Ex.: 12 ou LAB-01" autocomplete="off" required>
                </div>

                <div class="field">
                    <label for="room-block">Bloco</label>
                    <input id="room-block" name="bloco" type="text" maxlength="50" placeholder="Ex.: A" autocomplete="off" required>
                </div>

                <div class="user-actions">
                    <button class="button" type="button" id="cancel-room">Cancelar</button>
                    <button class="button button--primary" type="submit" disabled>Salvar</button>
                </div>
            </form>
        </div>
    </main>

    <script src="../js/app-profile.js?v=staff-access-1"></script>
    <script src="../js/app-services.js?v=staff-access-1"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/menu-lateral.js?v=staff-access-1" defer></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/cadastrodesalas.js?v=room-admin-2"></script>
</body>
</html>
