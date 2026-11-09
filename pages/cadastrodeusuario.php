<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['GERENTE']); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro de Usuário</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/cadastrodeusuario.css">
    <link rel="stylesheet" href="../css/menu-lateral.css">
</head>
<body data-profiles="GERENTE" data-auth-state="pending">
    <div id="menu-lateral-container" data-page="usuarios" data-menu-url="menu-lateral.html"></div>

    <main class="app-page">
        <div class="user-wrap">
            <header class="user-header">
                <img class="brand-logo" src="../assets/icons/Logo%20sistema%20de%20chaves.svg" alt="Sistema de gerenciamento de chaves">
                <h1>Usuários</h1>
            </header>

            <section class="user-list-section" aria-labelledby="users-list-title">
                <div class="section-heading">
                    <h2 id="users-list-title">Usuários cadastrados</h2>
                    <button class="button button--primary" id="new-user" type="button">Cadastrar usuário</button>
                </div>
                <p id="users-list-feedback" class="form-feedback" role="status" aria-live="polite"></p>
                <div class="table-wrap">
                    <table class="users-table">
                        <thead><tr><th>Nome</th><th>CPF</th><th>Cargo</th><th>Telefone</th><th>E-mail</th><th>Ações</th></tr></thead>
                        <tbody id="users-table-body">
                            <tr id="users-empty"><td colspan="6">Nenhum usuário cadastrado.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <form id="user-form" class="user-form" novalidate>
                <h2 id="user-form-title">Cadastrar usuário</h2>
                <p id="user-feedback" class="form-feedback" role="status"></p>

                <div class="field">
                    <label for="user-name">Nome</label>
                    <input id="user-name" type="text" required minlength="3" placeholder="Digite o nome" autocomplete="name">
                </div>

                <div class="field">
                    <label for="user-role">Cargo</label>
                    <select id="user-role" class="select-control" required>
                        <option value="">Selecione o cargo</option>
                        <option value="PROFESSOR">PROFESSOR</option>
                        <option value="PORTEIRO">PORTEIRO</option>
                    </select>
                </div>

                <div class="field">
                    <label for="user-cpf">CPF</label>
                    <input id="user-cpf" type="text" inputmode="numeric" autocomplete="off" maxlength="14" placeholder="000.000.000-00" required>
                    <small class="field-hint">A senha inicial será definida pelos 4 primeiros dígitos do CPF.</small>
                </div>

                <div class="field">
                    <label for="user-phone">Telefone</label>
                    <input id="user-phone" type="tel" required placeholder="(00) 00000-0000" autocomplete="tel">
                </div>

                <div class="field">
                    <label for="user-email">Email</label>
                    <input id="user-email" type="email" required placeholder="email@escola.com" autocomplete="email">
                </div>

                <div class="user-actions">
                    <button class="button" type="button" id="cancel-user-edit">Cancelar</button>
                    <button class="button button--primary" type="submit">Salvar</button>
                </div>
            </form>
        </div>
    </main>

    <script src="../js/app-profile.js"></script>
    <script src="../js/app-services.js"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/menu-lateral.js" defer></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/cadastrodeusuario.js"></script>
</body>
</html>
