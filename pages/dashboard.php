<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['GERENTE', 'PORTEIRO']); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/global.css?v=key-identity-2">
    <link rel="stylesheet" href="../css/dashboard.css?v=history-alerts-1">
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

            <section class="panel overdue-panel" aria-labelledby="overdue-title">
                <header class="overdue-heading">
                    <h2 id="overdue-title">Alertas de atraso</h2>
                    <span id="overdue-count" class="overdue-count">—</span>
                </header>
                <div id="overdue-list" class="overdue-list" aria-live="polite">
                    <p class="data-state" data-state="loading">Consultando devoluções atrasadas...</p>
                </div>
            </section>

            <section class="dashboard-grid">
                <article class="panel movements-panel">
                    <header class="panel-title">
                        <h2>Últimas movimentações</h2>
                        <div class="panel-actions">
                            <a href="historico.php">Ver histórico completo</a>
                            <a href="listadechaves.php">Ver chaves</a>
                        </div>
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

    <script src="../js/app-profile.js?v=staff-access-1"></script>
    <script src="../js/app-services.js?v=staff-role-label-1"></script>
    <script src="../js/key-display.js?v=key-identity-2"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/menu-lateral.js?v=staff-access-1" defer></script>
    <script src="../js/dashboard.js?v=staff-role-label-1"></script>
</body>
</html>
