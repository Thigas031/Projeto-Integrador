<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['GERENTE']); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Salas</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/menu-lateral.css">
    <link rel="stylesheet" href="../css/cadastrodeusuario.css">
    <link rel="stylesheet" href="../css/listadesalas.css">
</head>
<body data-profiles="GERENTE" data-auth-state="pending">
    <div id="menu-lateral-container" data-page="cadastro-salas" data-menu-url="menu-lateral.html"></div>

    <main class="app-page">
        <section class="user-wrap room-list-wrap">
            <header class="user-header room-list-header">
                <img class="brand-logo" src="../assets/icons/Logo%20sistema%20de%20chaves.svg" alt="">
                <div class="room-list-heading">
                    <h1>Administração de salas</h1>
                    <p>Consulte e mantenha as salas usadas no cadastro de chaves.</p>
                </div>
                <div class="room-list-actions">
                    <a class="button" href="dashboard.php">Dashboard</a>
                    <a class="button button--primary" href="cadastrodesalas.php">Nova sala</a>
                </div>
            </header>

            <div class="room-list-section">
                <div class="room-search">
                    <label for="room-search">Pesquisar por nome, número ou bloco</label>
                    <input id="room-search" type="search" placeholder="Pesquisar salas..." autocomplete="off">
                </div>
                <p id="rooms-feedback" class="form-feedback" role="status" aria-live="polite"></p>

                <div class="table-wrap">
                    <table class="users-table room-table">
                        <thead>
                            <tr>
                                <th>Nome da sala</th>
                                <th>Número</th>
                                <th>Bloco</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="rooms-table-body">
                            <tr><td colspan="4">Carregando salas...</td></tr>
                        </tbody>
                    </table>
                </div>
                <p id="rooms-count" class="room-count" aria-live="polite"></p>
            </div>
        </section>
    </main>

    <script src="../js/app-profile.js?v=profile-matrix-2"></script>
    <script src="../js/app-services.js?v=profile-matrix-2"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/menu-lateral.js?v=profile-matrix-2" defer></script>
    <script src="../js/listadesalas.js?v=room-list-1"></script>
</body>
</html>
