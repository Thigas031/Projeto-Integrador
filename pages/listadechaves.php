<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['GERENTE', 'PORTEIRO']); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de chaves</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/menu-lateral.css">
    <link rel="stylesheet" href="../css/listadechaves.css">
</head>
<body data-profiles="GERENTE,PORTEIRO" data-auth-state="pending">
    <div id="menu-lateral-container" data-page="lista-chaves" data-menu-url="menu-lateral.html"></div>
    <main>
        <header class="cabecalho">
            <form id="form-busca">
                <label for="busca">Pesquisar sala ou chave</label>
                <input type="search" id="busca" name="busca" placeholder="Pesquisar sala ou chave...">
            </form>

            <button id="nova-chave" type="button">Nova chave</button>
        </header>

        <section class="lista-chaves">
            <h1>Lista de chaves</h1>

            <table id="tabela-chaves">
                <thead>
                    <tr>
                        <th>Nome da chave / sala</th>
                        <th>Número</th>
                        <th>Status</th>
                        <th>Responsável</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="corpo-tabela" aria-live="polite">
                    <tr id="linha-sem-resultados" hidden>
                        <td colspan="5">Nenhuma chave cadastrada.</td>
                    </tr>
                </tbody>
            </table>

            <div class="rodape-tabela">
                <p id="quantidade">Nenhuma chave cadastrada.</p>
                <nav class="paginacao" aria-label="Paginação">
                    <button type="button" data-page-action="previous" aria-label="Página anterior" disabled>Anterior</button>
                    <div class="paginas" id="paginas"></div>
                    <button type="button" data-page-action="next" aria-label="Próxima página" disabled>Próxima</button>
                </nav>
            </div>
        </section>

        <section class="acoes-rapidas" data-profiles="PORTEIRO" hidden>
            <button id="retirar-chave" type="button" data-profiles="PORTEIRO">
                <span class="icone-retirar" aria-hidden="true"><img src="../assets/icons/retirar chave.svg" alt=""></span>
                <strong>Retirar a chave</strong>
                <small>Registrar a retirada de uma chave</small>
            </button>
            <button id="devolver-chave" type="button" data-profiles="PORTEIRO">
                <span class="icone-devolver" aria-hidden="true"><img src="../assets/icons/devolver chave.svg" alt=""></span>
                <strong>Devolver chave</strong>
                <small>Registrar a devolução de uma chave</small>
            </button>
        </section>

        <p id="key-list-feedback" class="list-feedback" role="status" aria-live="polite"></p>

        <dialog id="manager-movement-dialog">
            <form id="manager-movement-form" method="dialog">
                <h2>Movimentar chave</h2>
                <p id="manager-movement-key"></p>
                <label for="manager-movement-user">Responsável</label>
                <select id="manager-movement-user" required>
                    <option value="">Selecione um usuário cadastrado</option>
                </select>
                <p id="manager-movement-feedback" class="list-feedback" role="status"></p>
                <div class="dialog-actions">
                    <button type="button" data-manager-movement-cancel>Cancelar</button>
                    <button type="submit" class="primary">Confirmar movimentação</button>
                </div>
            </form>
        </dialog>
    </main>

    <script src="../js/app-profile.js"></script>
    <script src="../js/app-services.js"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/menu-lateral.js?v=withdrawal-actions-1" defer></script>
    <script src="../js/listadechaves.js?v=withdrawal-actions-1"></script>
</body>
</html>
