<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['PORTEIRO']); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retirada de Chave</title>

    <link rel="stylesheet" href="../css/retiradadechaves.css">
    <link rel="stylesheet" href="../css/menu-lateral.css">

    <!-- Ícones -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
    <link rel="stylesheet" href="../css/global.css">
</head>

<body data-profiles="PORTEIRO" data-auth-state="pending">

<div class="app">
    <div id="menu-lateral-container" data-page="retirada-chaves" data-menu-url="menu-lateral.html"></div>


    <!-- =========================
         CONTEÚDO
    ========================== -->
    <main class="main">

        <!-- TÍTULO -->
        <div class="page-header">

            <div>
                <h1>Retirada de Chave</h1>

                <p>
                    Preencha os dados para registrar a retirada da chave.
                </p>
            </div>

        </div>


        <!-- =========================
             CARD DO FORMULÁRIO
        ========================== -->
        <section class="card">

            <div class="card-title">
                <h2>Dados da retirada</h2>
            </div>


            <form id="withdrawal-form" novalidate>

                <p id="withdrawal-feedback" class="withdrawal-feedback" role="status"></p>

                <div class="form-grid">

                    <!-- SALA -->
                    <div class="form-group">

                        <label for="sala">
                            Sala
                        </label>

                        <div class="input-wrapper">
                            <i class="fa-solid fa-door-open"></i>

                            <input id="sala" type="text" readonly placeholder="Será preenchido pela chave selecionada" required>
                        </div>

                    </div>


                    <!-- USUÁRIO -->
                    <div class="form-group">

                        <label for="usuario">
                            Usuário
                        </label>

                        <div class="input-wrapper">
                            <i class="fa-solid fa-user"></i>

                            <select id="usuario" class="select-control" required>
                                <option value="">Selecione o usuário cadastrado</option>
                            </select>
                        </div>

                    </div>


                    <!-- NÚMERO DA CHAVE -->
                    <div class="form-group">

                        <label for="chave">
                            Número da chave
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-key"></i>

                            <select id="chave" class="select-control" required>

                                <option value="">Selecione uma chave disponível</option>
                            </select>

                        </div>

                    </div>


                    <!-- DATA -->
                    <div class="form-group">

                        <label for="data">
                            Data
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-calendar"></i>

                            <input
                                type="date"
                                id="data"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- HORA -->
                    <div class="form-group">

                        <label for="hora">
                            Hora
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-clock"></i>

                            <input
                                type="time"
                                id="hora"
                                readonly
                            >

                        </div>

                    </div>

                </div>


                <!-- OBSERVAÇÕES -->
                <div class="form-group observacoes">

                    <label for="observacoes">
                        Observações
                    </label>

                    <textarea
                        id="observacoes"
                        placeholder="Digite alguma observação..."
                    ></textarea>

                </div>


                <!-- BOTÕES -->
                <div class="form-actions">

                    <button
                        type="button"
                        class="btn btn-cancelar"
                        data-cancel
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-confirmar"
                    >
                        <i class="fa-solid fa-check"></i>

                        Confirmar retirada
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

<script src="../js/app-profile.js"></script>
<script src="../js/app-services.js"></script>
<script src="../js/page-access.js"></script>
<script src="../js/menu-lateral.js" defer></script>
<script src="../js/form-feedback.js"></script>
<script src="../js/retiradadechaves.js"></script>
</body>
</html>