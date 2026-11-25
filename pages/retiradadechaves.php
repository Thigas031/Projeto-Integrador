<?php require_once __DIR__ . '/../controllers/controller_page_access.php'; requirePageAccess(['PORTEIRO']); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retirada de Chave</title>

    <link rel="stylesheet" href="../css/retiradadechaves.css?v=external-borrower-1">
    <link rel="stylesheet" href="../css/menu-lateral.css">

    <!-- Ícones -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
    <link rel="stylesheet" href="../css/global.css?v=key-identity-2">
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


            <form id="withdrawal-form" action="../controllers/controller_movimentacao_retirar.php" method="post" novalidate>

                <p id="withdrawal-feedback" class="withdrawal-feedback" role="status"></p>

                <div class="form-grid">

                    <!-- SALA -->
                    <div class="form-group">

                        <label for="sala">
                            Sala
                        </label>

                        <div class="input-wrapper">
                            <i class="fa-solid fa-door-open"></i>

                            <select id="sala" name="sala" class="select-control" required>
                                <option value="">Selecione uma sala</option>
                            </select>
                        </div>

                    </div>


                    <!-- RESPONSÁVEL -->
                    <div class="form-group">

                        <label for="usuario-busca">Usuário responsável</label>

                        <div class="responsible-autocomplete">
                            <div class="input-wrapper">
                                <i class="fa-solid fa-user"></i>
                                <input
                                    id="usuario-busca"
                                    type="text"
                                    placeholder="Busque e selecione um usuário ou Outro"
                                    autocomplete="off"
                                    role="combobox"
                                    aria-autocomplete="list"
                                    aria-expanded="false"
                                    aria-controls="responsible-options"
                                    required
                                >
                            </div>
                            <input id="usuario" name="usuario" type="hidden">
                            <div id="responsible-options" class="responsible-options" role="listbox" hidden></div>
                        </div>

                    </div>

                    <div id="external-name-field" class="form-group" hidden>
                        <label for="nome-retirante">Nome da pessoa</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-user"></i>
                            <input
                                id="nome-retirante"
                                name="nome_retirante"
                                type="text"
                                maxlength="100"
                                placeholder="Digite o nome da pessoa"
                            >
                        </div>
                    </div>

                    <div id="external-role-field" class="form-group" hidden>
                        <label for="cargo-retirante">Cargo</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-briefcase"></i>
                            <input
                                id="cargo-retirante"
                                name="cargo_retirante"
                                type="text"
                                maxlength="100"
                                placeholder="Cargo da pessoa"
                            >
                        </div>
                    </div>


                    <!-- CHAVE -->
                    <div class="form-group">

                        <label for="chave">Chave</label>

                        <div class="input-wrapper">
                            <i class="fa-solid fa-key"></i>
                            <select id="chave" name="chave" class="select-control" required>
                                <option value="">Selecione uma chave disponível</option>
                            </select>
                        </div>
                        <div id="key-selection-summary" class="key-selection-summary" hidden aria-live="polite"></div>
                    </div>

                    <fieldset class="form-group time-fields">
                        <legend>Data e horário da retirada</legend>
                        <div class="time-inputs">
                            <div class="form-group">
                                <label for="data">Data</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-calendar"></i>
                                    <input type="date" id="data" name="data" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="hora">Hora</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-clock"></i>
                                    <input type="time" id="hora" name="hora" readonly>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-group forecast-fields">
                        <legend>Previsão de devolução</legend>
                        <p class="forecast-help">Definida inicialmente para 50 minutos após a retirada. Você pode alterar a data e o horário.</p>
                        <div class="forecast-inputs">
                            <div class="form-group">
                                <label for="data-prevista">Data prevista</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-calendar"></i>
                                    <input type="date" id="data-prevista" name="data_prevista" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="hora-prevista">Horário previsto</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-clock"></i>
                                    <input type="time" id="hora-prevista" name="hora_prevista" required>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                </div>


                <!-- OBSERVAÇÕES -->
                <div class="form-group observacoes">

                    <label for="observacoes">
                        Observações
                    </label>

                    <textarea
                        id="observacoes"
                        name="observacao"
                        placeholder="Digite alguma observação..."
                        maxlength="500"
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

<script src="../js/app-profile.js?v=staff-access-1"></script>
<script src="../js/app-services.js?v=staff-access-1"></script>
<script src="../js/key-display.js?v=key-identity-2"></script>
<script src="../js/page-access.js"></script>
<script src="../js/menu-lateral.js?v=staff-access-1" defer></script>
<script src="../js/form-feedback.js"></script>
<script src="../js/retiradadechaves.js?v=external-borrower-1"></script>
</body>
</html>