<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['GERENTE', 'PORTEIRO']); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ocorrências</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/menu-lateral.css">
    <link rel="stylesheet" href="../css/cadastrodeusuario.css">
</head>
<body data-profiles="GERENTE,PORTEIRO" data-auth-state="pending">
    <div id="menu-lateral-container" data-page="ocorrencias" data-menu-url="menu-lateral.html"></div>
    <main class="app-page">
        <section class="user-wrap occurrence-wrap">
            <header class="user-header">
                <img class="brand-logo" src="../assets/icons/Logo%20sistema%20de%20chaves.svg" alt="Sistema de gerenciamento de chaves">
                <h1>Ocorrências</h1>
            </header>
            <section class="user-list-section" aria-labelledby="occurrence-list-title">
                <div class="section-heading">
                    <h2 id="occurrence-list-title">Ocorrências registradas</h2>
                </div>
                <p id="occurrence-feedback" class="form-feedback" role="status" aria-live="polite"></p>
                <div class="table-wrap">
                    <table class="users-table">
                        <thead><tr><th>Data e hora</th><th>Registrada por</th><th>Descrição</th></tr></thead>
                        <tbody id="occurrence-table-body">
                            <tr><td colspan="3">Carregando ocorrências...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <form id="occurrence-form" class="user-form" data-profiles="PORTEIRO" hidden novalidate>
                <h2>Registrar ocorrência</h2>
                <p id="occurrence-form-feedback" class="form-feedback" role="status" aria-live="polite"></p>
                <div class="field">
                    <label for="occurrence-text">Descrição</label>
                    <textarea id="occurrence-text" rows="5" maxlength="2000" required placeholder="Descreva a ocorrência"></textarea>
                </div>
                <div class="user-actions">
                    <button class="button button--primary" type="submit">Registrar ocorrência</button>
                </div>
            </form>
        </section>
    </main>
    <script src="../js/app-profile.js?v=staff-access-1"></script>
    <script src="../js/app-services.js?v=staff-access-1"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/menu-lateral.js?v=staff-access-1" defer></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/ocorrencias.js"></script>
</body>
</html>
