<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['GERENTE']); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar chave</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/menu-lateral.css">
    <link rel="stylesheet" href="../css/cadastrodechaves.css">
</head>
<body data-profiles="GERENTE" data-auth-state="pending">
    <div id="menu-lateral-container" data-page="lista-chaves" data-menu-url="menu-lateral.html"></div>
    <main>
        <section class="cadastro-chaves">
            <header class="cadastro-chaves-header">
                <img class="brand-logo" src="../assets/icons/Logo%20sistema%20de%20chaves.svg" alt="Sistema de gerenciamento de chaves">
                <h1>Editar chave</h1>
                <span class="key-icon" aria-hidden="true"><img src="../assets/icons/Chave.svg" alt=""></span>
            </header>

            <div id="key-edit-summary" class="key-edit-summary" hidden></div>

            <form id="key-edit-form" class="form-cadastro-chaves" novalidate>
                <p id="key-edit-feedback" class="form-feedback" role="status" aria-live="polite"></p>

                <div class="campo-formulario">
                    <label for="key-edit-name">Nome da chave</label>
                    <input id="key-edit-name" type="text" maxlength="100" required>
                </div>

                <div class="campo-formulario">
                    <label for="key-edit-room">Sala cadastrada</label>
                    <select id="key-edit-room" required disabled>
                        <option value="">Carregando dados da chave...</option>
                    </select>
                </div>

                <div class="campo-formulario">
                    <label for="key-edit-number">Número da chave</label>
                    <input id="key-edit-number" type="text" maxlength="30" required>
                </div>

                <div class="campo-formulario">
                    <label for="key-edit-block">Bloco</label>
                    <input id="key-edit-block" type="text" readonly>
                </div>

                <div class="acoes-cadastro-chaves">
                    <button class="button" type="button" data-cancel>Cancelar</button>
                    <button class="button button--primary" type="submit" disabled>Salvar alterações</button>
                </div>
            </form>
        </section>
    </main>

    <script src="../js/app-profile.js?v=staff-access-1"></script>
    <script src="../js/app-services.js?v=staff-access-1"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/menu-lateral.js?v=staff-access-1" defer></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/editarchave.js?v=key-editor-1"></script>
</body>
</html>
