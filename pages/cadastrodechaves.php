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
</head>
<body data-profiles="GERENTE,PORTEIRO" data-auth-state="pending">
    <div id="menu-lateral-container" data-page="cadastro-chaves" data-menu-url="menu-lateral.html"></div>

    <main>
        <section class="cadastro-chaves">
            <header class="cadastro-chaves-header">
                <img class="brand-logo" src="../assets/icons/Logo%20sistema%20de%20chaves.svg" alt="Sistema de gerenciamento de chaves">
                <h1>Cadastro de chave</h1>
                <span class="key-icon" aria-hidden="true"><img src="../assets/icons/Chave.svg" alt=""></span>
            </header>

            <form id="key-form" class="form-cadastro-chaves" novalidate>
                <p id="key-feedback" class="form-feedback" role="status"></p>

                <div class="campo-formulario">
                    <label for="nome-chave">Nome da chave</label>
                    <input type="text" id="nome-chave" name="keyName" placeholder="Ex.: Chave da sala de reuniões" maxlength="100" required>
                </div>

                <div class="campo-formulario">
                    <label for="nome-sala">Sala cadastrada</label>
                    <select id="nome-sala" name="sala" required disabled>
                        <option value="">Carregando salas...</option>
                    </select>
                    <a id="manage-rooms-link" class="room-management-link" href="listadesalas.php" hidden>
                        Abrir administração de salas
                    </a>
                </div>

                <div class="campo-formulario">
                    <label for="numero-chave">Número da chave</label>
                    <input type="text" id="numero-chave" name="number" placeholder="Ex.: 1" maxlength="30" required>
                </div>

                <div class="campo-formulario">
                    <label for="bloco">Bloco</label>
                    <input type="text" id="bloco" name="block" placeholder="Preenchido pela sala selecionada" maxlength="50" readonly>
                </div>

                <div class="acoes-cadastro-chaves">
                    <button class="button" type="button" data-cancel>Cancelar</button>
                    <button class="button button--primary" type="submit" disabled>Salvar</button>
                </div>
            </form>
        </section>
    </main>

    <script src="../js/app-profile.js?v=profile-matrix-2"></script>
    <script src="../js/app-services.js?v=key-name-1"></script>
    <script src="../js/page-access.js"></script>
    <script src="../js/menu-lateral.js?v=profile-matrix-2" defer></script>
    <script src="../js/form-feedback.js"></script>
    <script src="../js/cadastrodechaves.js?v=key-name-1"></script>
</body>
</html>
