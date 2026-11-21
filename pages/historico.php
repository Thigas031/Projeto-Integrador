<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['GERENTE', 'PORTEIRO']); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Histórico e auditoria</title>
    <link rel="stylesheet" href="../css/global.css?v=history-audit-1">
    <link rel="stylesheet" href="../css/menu-lateral.css">
    <link rel="stylesheet" href="../css/historico.css?v=history-audit-1">
</head>
<body data-profiles="GERENTE,PORTEIRO,OUTRO" data-auth-state="pending">
    <div id="menu-lateral-container" data-page="historico" data-menu-url="menu-lateral.html"></div>

    <main class="app-page">
        <div class="history-wrap">
            <header class="history-header">
                <img src="../assets/icons/Logo%20sistema%20de%20chaves.svg" alt="Sistema de gerenciamento de chaves">
                <div>
                    <h1>Histórico / Auditoria</h1>
                    <p>Consulte retiradas, devoluções e atrasos registrados no sistema.</p>
                </div>
                <a class="history-back" href="dashboard.php">Voltar à Dashboard</a>
            </header>

            <p id="history-feedback" class="history-feedback" role="status" aria-live="polite"></p>

            <div class="history-layout">
                <section class="history-card" aria-labelledby="history-table-title">
                    <header class="history-card-heading">
                        <div>
                            <h2 id="history-table-title">Relatório de movimentações</h2>
                            <p id="history-count">Carregando movimentações...</p>
                        </div>
                        <button class="history-primary" id="history-export" type="button">Gerar relatório</button>
                    </header>
                    <div class="history-table-wrap">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th scope="col">Sala</th>
                                    <th scope="col">Chave</th>
                                    <th scope="col">Pessoa que retirou</th>
                                    <th scope="col">Registrada por</th>
                                    <th scope="col">Retirada</th>
                                    <th scope="col">Prevista</th>
                                    <th scope="col">Devolução</th>
                                    <th scope="col">Situação</th>
                                    <th scope="col">Atraso</th>
                                </tr>
                            </thead>
                            <tbody id="history-rows" aria-live="polite">
                                <tr><td colspan="9">Carregando movimentações...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <aside class="history-filters" aria-labelledby="filters-title">
                    <h2 id="filters-title">Filtros</h2>
                    <form id="history-filter-form">
                        <div class="history-field">
                            <label for="history-start">Período — de</label>
                            <input id="history-start" name="data_inicial" type="date">
                        </div>
                        <div class="history-field">
                            <label for="history-end">Até</label>
                            <input id="history-end" name="data_final" type="date">
                        </div>
                        <div class="history-field">
                            <label for="history-room">Sala</label>
                            <select id="history-room" name="sala">
                                <option value="">Todas as salas</option>
                            </select>
                        </div>
                        <div class="history-field">
                            <label for="history-user">Usuário / retirante</label>
                            <select id="history-user" name="usuario">
                                <option value="">Todas as pessoas</option>
                            </select>
                        </div>
                        <div class="history-field">
                            <label for="history-status">Status</label>
                            <select id="history-status" name="status">
                                <option value="Todos">Todos</option>
                                <option value="Pendente">Pendente</option>
                                <option value="Atrasada">Atrasada</option>
                                <option value="Devolvida">Devolvida</option>
                            </select>
                        </div>
                        <button class="history-primary history-apply" type="submit">Aplicar filtros</button>
                        <button class="history-secondary" id="history-clear" type="button">Limpar filtros</button>
                    </form>
                </aside>
            </div>
        </div>
    </main>

    <script src="../js/app-profile.js?v=profile-matrix-2"></script>
    <script src="../js/app-services.js?v=history-audit-1"></script>
    <script src="../js/key-display.js?v=key-identity-2"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/menu-lateral.js?v=profile-matrix-2" defer></script>
    <script src="../js/historico.js?v=history-audit-1"></script>
</body>
</html>
