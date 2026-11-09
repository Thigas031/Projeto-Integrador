<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['GERENTE', 'PORTEIRO']); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de chave</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/menu-lateral.css">
    <link rel="stylesheet" href="../css/cadastrodechaves.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body data-profiles="GERENTE,PORTEIRO" data-auth-state="pending">
    <div id="menu-lateral-container" data-page="cadastro-chaves" data-menu-url="menu-lateral.html"></div>

    <main>
        <section class="cadastro-chaves">
            <header class="cadastro-chaves-header">
                <img class="brand-logo" src="../assets/icons/Logo%20sistema%20de%20chaves.svg" alt="Sistema de gerenciamento de chaves">
                <h1>Cadastro de chave</h1>
                <span class="key-icon" aria-hidden="true"><i class="fa-solid fa-key"></i></span>
            </header>

            <form id="key-form" class="form-cadastro-chaves" novalidate>
                <p id="key-feedback" class="form-feedback" role="status"></p>

                <div class="campo-formulario">
                    <label for="nome-sala">Nome da chave ou sala</label>
                    <input type="text" id="nome-sala" name="name" placeholder="Digite o nome da sala ou local" maxlength="100" required>
                </div>

                <div class="campo-formulario">
                    <label for="numero-chave">Número da chave</label>
                    <input type="text" id="numero-chave" name="number" placeholder="Ex.: 1" maxlength="30" required>
                </div>

                <div class="campo-formulario">
                    <label for="bloco">Bloco</label>
                    <input type="text" id="bloco" name="block" placeholder="Ex.: A" maxlength="50">
                </div>

                <div class="acoes-cadastro-chaves">
                    <button class="button" type="button" data-cancel>Cancelar</button>
                    <button class="button button--primary" type="submit">Salvar</button>
                </div>
            </form>
        </section>
    </main>

    <script src="../js/app-profile.js"></script>
    <script src="../js/app-services.js"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/menu-lateral.js" defer></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/cadastrodechaves.js"></script>
</body>
</html>
