<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['PORTEIRO']); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Devolução de Chave</title>
    <link rel="stylesheet" href="../css/global.css?v=key-identity-2">
    <link rel="stylesheet" href="../css/devolucaodechaves.css">
    <link rel="stylesheet" href="../css/menu-lateral.css">
</head>
<body data-profiles="PORTEIRO" data-auth-state="pending">
    <div id="menu-lateral-container" data-page="devolucao-chaves" data-menu-url="menu-lateral.html"></div>

    <main class="app-page">
        <div class="return-wrap">
            <header class="return-header">
                <img class="brand-logo" src="../assets/icons/Logo%20sistema%20de%20chaves.svg" alt="Sistema de gerenciamento de chaves">
                <h1>Devolução de Chave</h1>
            </header>

            <form id="return-form" class="return-form" novalidate>
                <p id="return-feedback" class="return-feedback" role="status"></p>

                <section class="return-section" aria-labelledby="key-section-title">
                    <h2 id="key-section-title">Dados da chave</h2>
                    <div class="return-fields return-fields--single">
                        <div class="return-field">
                            <label for="return-key">Chave</label>
                            <select id="return-key" class="select-control" required>
                                <option value="">Selecione uma chave</option>
                            </select>
                            <div id="key-selection-summary" class="key-selection-summary" hidden aria-live="polite"></div>
                        </div>
                    </div>
                </section>

                <section class="return-section" aria-labelledby="user-section-title">
                    <h2 id="user-section-title">Dados da pessoa responsável</h2>
                    <div class="return-field">
                        <label for="return-user">Nome</label>
                        <input id="return-user" type="text" readonly>
                    </div>
                    <div class="return-field">
                        <label for="return-role">Cargo</label>
                        <input id="return-role" type="text" readonly>
                    </div>
                </section>

                <section class="return-section" aria-labelledby="movement-section-title">
                    <h2 id="movement-section-title">Dados da retirada</h2>
                    <div class="return-fields">
                        <div class="return-field">
                            <label for="return-date">Data de retirada</label>
                            <input id="return-date" type="text" readonly>
                        </div>

                        <div class="return-field">
                            <label for="return-time">Hora da retirada</label>
                            <input id="return-time" type="text" readonly>
                        </div>
                    </div>
                </section>

                <div class="return-actions">
                    <button type="button" data-cancel>Cancelar</button>
                    <button class="confirm" type="submit">Confirmar devolução</button>
                </div>
            </form>
        </div>
    </main>

    <script src="../js/app-profile.js?v=staff-access-1"></script>
    <script src="../js/app-services.js?v=staff-role-label-1"></script>
    <script src="../js/key-display.js?v=key-identity-2"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/menu-lateral.js?v=staff-access-1" defer></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/devolucaodechaves.js?v=staff-role-label-1"></script>
</body>
</html>
