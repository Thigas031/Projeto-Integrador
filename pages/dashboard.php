<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['GERENTE', 'PORTEIRO']); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/menu-lateral.css">
</head>
<body data-profiles="GERENTE,PORTEIRO" data-auth-state="pending">
    <div id="menu-lateral-container" data-page="dashboard" data-menu-url="menu-lateral.html"></div>

    <main class="app-page">
        <div class="dashboard-wrap">
            <header class="dashboard-header">
                <img class="brand-logo" src="../assets/icons/Logo%20sistema%20de%20chaves.svg" alt="Sistema de gerenciamento de chaves">
                <h1 id="dashboard-greeting">Dashboard</h1>
            </header>

            <p id="dashboard-feedback" class="dashboard-feedback" role="status" aria-live="polite"></p>
            <section class="metrics" aria-label="Indicadores das chaves">
                <article class="metric metric--available">
                    <div class="metric-value">
                        <strong id="available">—</strong>
                        <img class="metric-icon" src="../assets/icons/Chave.svg" alt="" aria-hidden="true">
                    </div>
                    <span>Chaves disponíveis</span>
                </article>
                <article class="metric metric--borrowed">
                    <div class="metric-value">
                        <strong id="borrowed">—</strong>
                        <img class="metric-icon" src="../assets/icons/Chave.svg" alt="" aria-hidden="true">
                    </div>
                    <span>Emprestadas</span>
                </article>
                <article class="metric metric--late">
                    <div class="metric-value">
                        <strong id="late">—</strong>
                        <img class="metric-icon" src="../assets/icons/Chave.svg" alt="" aria-hidden="true">
                    </div>
                    <span>Atrasadas</span>
                </article>
            </section>

            <section class="dashboard-grid">
                <article class="panel movements-panel">
                    <header class="panel-title">
                        <h2>Últimas movimentações</h2>
                        <a href="listadechaves.php">Ver chaves</a>
                    </header>
                    <div id="movements" class="movement-list" aria-live="polite">
                        <p class="data-state" data-state="loading">Carregando movimentações...</p>
                    </div>
                </article>

                <aside class="dashboard-side">
                    <article class="panel side-panel side-panel--borrowed">
                        <h2>Chaves para devolver hoje</h2>
                        <div class="due-summary">
                            <div>
                                <strong class="due-count" id="due">—</strong>
                                <p>Chaves</p>
                            </div>
                            <img class="due-icon" src="../assets/icons/leve-embora%204.svg" alt="" aria-hidden="true">
                        </div>
                    </article>

                    <article class="panel side-panel side-panel--notes">
                        <h2>Ocorrências registradas</h2>
                        <p id="occurrence-summary">Carregando ocorrências...</p>
                        <a class="details-button" href="ocorrencias.php">Ver ocorrências</a>
                    </article>
                </aside>
            </section>
        </div>
    </main>

    <script src="../js/app-profile.js"></script>
    <script src="../js/app-services.js"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/menu-lateral.js" defer></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
