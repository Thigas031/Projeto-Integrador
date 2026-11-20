(function () {
window.AppAccess.run(function () {
    const form = document.getElementById("withdrawal-form");
    const feedback = document.getElementById("withdrawal-feedback");
    const roomSelect = document.getElementById("sala");
    const userSearchInput = document.getElementById("usuario-busca");
    const userIdInput = document.getElementById("usuario");
    const externalNameField = document.getElementById("external-name-field");
    const externalNameInput = document.getElementById("nome-retirante");
    const externalRoleField = document.getElementById("external-role-field");
    const userRoleInput = document.getElementById("cargo-retirante");
    const userOptions = document.getElementById("responsible-options");
    const keySelect = document.getElementById("chave");
    const keySummary = document.getElementById("key-selection-summary");
    const submitButton = form.querySelector('[type="submit"]');
    const requestedKey = new URLSearchParams(window.location.search).get("chave");
    const dataPrevistaField = document.getElementById("data-prevista");
    const horaPrevistaField = document.getElementById("hora-prevista");
    let availableKeys = [];
    let activeUsers = [];
    let previsaoAlteradaManualmente = false;
    let highlightedUserIndex = -1;
    let borrowerType = "";

    function formatDate(value) {
        return value.getFullYear() + "-" +
            String(value.getMonth() + 1).padStart(2, "0") + "-" +
            String(value.getDate()).padStart(2, "0");
    }

    function formatTime(value) {
        return String(value.getHours()).padStart(2, "0") + ":" +
            String(value.getMinutes()).padStart(2, "0");
    }

    function setLocalDateTime(now) {
        document.getElementById("data").value = formatDate(now);
        document.getElementById("hora").value = formatTime(now);
    }

    function setDefaultReturnForecast(withdrawalTime) {
        const due = new Date(withdrawalTime.getTime() + 50 * 60 * 1000);
        dataPrevistaField.value = formatDate(due);
        horaPrevistaField.value = formatTime(due);
    }

    function atualizarMinimoPrevisto() {
        const agora = new Date();
        const localAgora = new Date(agora.getTime() - agora.getTimezoneOffset() * 60000);
        const limiteHora = new Date(agora.getTime());
        limiteHora.setSeconds(0, 0);
        limiteHora.setMinutes(limiteHora.getMinutes() + 2);
        const localLimite = new Date(limiteHora.getTime() - limiteHora.getTimezoneOffset() * 60000);

        dataPrevistaField.min = localAgora.toISOString().slice(0, 10);
        if (dataPrevistaField.value === dataPrevistaField.min) {
            horaPrevistaField.min = localLimite.toISOString().slice(11, 16);
        } else {
            horaPrevistaField.removeAttribute("min");
        }
    }

    function previsaoFuturaValida(data, hora) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(data) || !/^\d{2}:\d{2}$/.test(hora)) {
            return false;
        }

        const [ano, mes, dia] = data.split("-").map(Number);
        const [horas, minutos] = hora.split(":").map(Number);
        const prevista = new Date(ano, mes - 1, dia, horas, minutos);
        return prevista.getFullYear() === ano &&
            prevista.getMonth() === mes - 1 &&
            prevista.getDate() === dia &&
            prevista.getHours() === horas &&
            prevista.getMinutes() === minutos &&
            prevista.getTime() > Date.now();
    }

    function populate(select, placeholder, items, label) {
        select.replaceChildren();
        const first = document.createElement("option");
        first.value = "";
        first.textContent = placeholder;
        select.appendChild(first);
        items.forEach(function (item) {
            const option = document.createElement("option");
            option.value = item.id;
            option.textContent = label(item);
            select.appendChild(option);
        });
    }

    function normalizarBusca(value) {
        return value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLocaleLowerCase("pt-BR");
    }

    function fecharOpcoes() {
        userOptions.hidden = true;
        userSearchInput.setAttribute("aria-expanded", "false");
        userSearchInput.removeAttribute("aria-activedescendant");
        highlightedUserIndex = -1;
    }

    function renderizarUsuariosCorrespondentes() {
        const termoOriginal = userSearchInput.value.trim();
        const termo = normalizarBusca(termoOriginal);
        userOptions.replaceChildren();
        highlightedUserIndex = -1;

        const correspondencias = (termo
            ? activeUsers.filter(function (user) {
                return normalizarBusca(user.name).includes(termo);
            })
            : activeUsers
        ).slice(0, 8);

        correspondencias.forEach(function (user, index) {
            const option = document.createElement("button");
            option.type = "button";
            option.id = "responsible-option-" + user.id;
            option.className = "responsible-option";
            option.role = "option";
            option.dataset.userId = String(user.id);
            option.setAttribute("aria-selected", "false");
            option.textContent = user.name + (user.role ? " - " + user.role : "");
            option.addEventListener("mousedown", function (event) {
                event.preventDefault();
                selecionarUsuario(user);
            });
            userOptions.appendChild(option);
            if (index === 0) userSearchInput.setAttribute("aria-activedescendant", option.id);
        });

        const outraOpcao = document.createElement("button");
        outraOpcao.type = "button";
        outraOpcao.id = "responsible-option-other";
        outraOpcao.className = "responsible-option";
        outraOpcao.role = "option";
        outraOpcao.dataset.optionType = "other";
        outraOpcao.setAttribute("aria-label", "Outro, pessoa sem cadastro");
        outraOpcao.setAttribute("aria-selected", "false");
        outraOpcao.textContent = "Outro";
        outraOpcao.addEventListener("mousedown", function (event) {
            event.preventDefault();
            selecionarOutro(termoOriginal);
        });
        userOptions.appendChild(outraOpcao);

        if (!correspondencias.length) {
            userSearchInput.setAttribute("aria-activedescendant", outraOpcao.id);
        }
        userOptions.hidden = false;
        userSearchInput.setAttribute("aria-expanded", "true");
    }

    function selecionarUsuario(user) {
        borrowerType = "registered";
        userSearchInput.value = user.name + (user.role ? " - " + user.role : "");
        userIdInput.value = String(user.id);
        configurarCamposExternos(false);
        fecharOpcoes();
    }

    function selecionarOutro(nomeDigitado) {
        borrowerType = "external";
        userSearchInput.value = "Outro";
        userIdInput.value = "";
        configurarCamposExternos(true);
        externalNameInput.value = nomeDigitado && normalizarBusca(nomeDigitado) !== "outro"
            ? nomeDigitado
            : "";
        fecharOpcoes();
        window.setTimeout(function () { externalNameInput.focus(); }, 0);
    }

    function configurarCamposExternos(exibir) {
        externalNameField.hidden = !exibir;
        externalRoleField.hidden = !exibir;
        externalNameInput.required = exibir;
        userRoleInput.required = exibir;
        if (!exibir) {
            externalNameInput.value = "";
            userRoleInput.value = "";
        }
    }

    function limparSelecaoUsuario() {
        borrowerType = "";
        userIdInput.value = "";
        configurarCamposExternos(false);
    }

    function destacarOpcaoUsuario(index) {
        const options = Array.from(userOptions.querySelectorAll('[role="option"]'));
        if (!options.length) return;
        highlightedUserIndex = (index + options.length) % options.length;
        options.forEach(function (option, optionIndex) {
            option.setAttribute("aria-selected", optionIndex === highlightedUserIndex ? "true" : "false");
        });
        userSearchInput.setAttribute("aria-activedescendant", options[highlightedUserIndex].id);
    }

    function confirmarOpcaoDestacada() {
        if (highlightedUserIndex < 0) return false;
        const option = userOptions.querySelectorAll('[role="option"]')[highlightedUserIndex];
        if (option && option.dataset.optionType === "other") {
            selecionarOutro(userSearchInput.value.trim());
            return true;
        }
        const user = activeUsers.find(function (item) { return option && String(item.id) === option.dataset.userId; });
        if (!user) return false;
        selecionarUsuario(user);
        return true;
    }

    function updateKeySummary() {
        const key = availableKeys.find(function (item) {
            return String(item.id) === keySelect.value;
        });
        keySummary.replaceChildren();
        keySummary.hidden = !key;
        if (key) keySummary.appendChild(window.AppKeyDisplay.create(key));
    }

    async function initialize() {
        try {
            const options = await window.AppServices.movements.options();
            availableKeys = options.chaves;
            activeUsers = options.usuarios;
            if (document.activeElement === userSearchInput) renderizarUsuariosCorrespondentes();
            populate(roomSelect, "Selecione uma sala", options.salas, function (room) {
                return room.name + " — Nº " + room.numero + " — Bloco " + room.bloco;
            });
            const chaveSolicitada = availableKeys.find(function (key) {
                return String(key.id) === requestedKey;
            });
            if (chaveSolicitada) {
                roomSelect.value = String(chaveSolicitada.salaId);
                carregarChaves(roomSelect.value, chaveSolicitada.id);
            } else {
                carregarChaves(roomSelect.value);
                if (requestedKey) {
                    window.AppFeedback.show(feedback, "A chave selecionada não está disponível para retirada.", "error");
                }
            }

            submitButton.disabled = !availableKeys.length;
            if (!availableKeys.length) {
                window.AppFeedback.show(feedback, "Não há chaves disponíveis para retirada.", "error");
            }
        } catch (error) {
            submitButton.disabled = true;
            window.AppFeedback.show(feedback, error.message || "Não foi possível carregar as opções de retirada.", "error");
        }
    }

    function carregarChaves(idSala, idChaveSelecionada) {
        const chavesDaSala = idSala
            ? availableKeys.filter(function (key) { return String(key.salaId) === String(idSala); })
            : availableKeys;
        const placeholder = !idSala
            ? chavesDaSala.length ? "Selecione uma chave disponível" : "Nenhuma chave disponível"
            : chavesDaSala.length ? "Selecione uma chave disponível" : "Nenhuma chave disponível nesta sala";
        populate(keySelect, placeholder, chavesDaSala, function (key) {
            return window.AppKeyDisplay.optionLabel(key);
        });
        keySelect.disabled = !chavesDaSala.length;
        if (idChaveSelecionada !== undefined) {
            keySelect.value = String(idChaveSelecionada);
        }
        updateKeySummary();
    }

    roomSelect.addEventListener("change", function () {
        carregarChaves(roomSelect.value);
    });
    userSearchInput.addEventListener("input", function () {
        limparSelecaoUsuario();
        renderizarUsuariosCorrespondentes();
    });
    userSearchInput.addEventListener("focus", renderizarUsuariosCorrespondentes);
    userSearchInput.addEventListener("keydown", function (event) {
        if (event.key === "ArrowDown" && !userOptions.hidden) {
            event.preventDefault();
            destacarOpcaoUsuario(highlightedUserIndex + 1);
        } else if (event.key === "ArrowUp" && !userOptions.hidden) {
            event.preventDefault();
            destacarOpcaoUsuario(highlightedUserIndex < 0 ? 0 : highlightedUserIndex - 1);
        } else if (event.key === "Enter" && !userOptions.hidden) {
            if (highlightedUserIndex < 0) destacarOpcaoUsuario(0);
            if (confirmarOpcaoDestacada()) event.preventDefault();
        } else if (event.key === "Escape") {
            fecharOpcoes();
        }
    });
    userSearchInput.addEventListener("blur", function () {
        window.setTimeout(fecharOpcoes, 100);
    });
    dataPrevistaField.addEventListener("input", function () { previsaoAlteradaManualmente = true; });
    horaPrevistaField.addEventListener("input", function () { previsaoAlteradaManualmente = true; });
    dataPrevistaField.addEventListener("change", atualizarMinimoPrevisto);
    keySelect.addEventListener("change", function () {
        if (!keySelect.value) {
            updateKeySummary();
            return;
        }
        const key = availableKeys.find(function (item) {
            return String(item.id) === keySelect.value;
        });
        if (key && String(key.salaId) !== roomSelect.value) {
            roomSelect.value = String(key.salaId);
            carregarChaves(roomSelect.value, key.id);
        }
        updateKeySummary();
    });
    form.querySelector("[data-cancel]").addEventListener("click", function () {
        window.location.href = "listadechaves.php";
    });

    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        const withdrawalTime = new Date();
        setLocalDateTime(withdrawalTime);
        if (!previsaoAlteradaManualmente) {
            setDefaultReturnForecast(withdrawalTime);
        }
        atualizarMinimoPrevisto();

        if (!roomSelect.value || !keySelect.value) {
            window.AppFeedback.show(feedback, "Selecione uma sala e uma chave disponível.", "error");
            return;
        }
        if (borrowerType === "") {
            window.AppFeedback.show(feedback, "Selecione um usuário cadastrado ou a opção Outro.", "error");
            return;
        }
        if (borrowerType === "external" && !externalNameInput.value.trim()) {
            window.AppFeedback.show(feedback, "Informe o nome da pessoa não cadastrada.", "error");
            return;
        }
        if (borrowerType === "external" && !userRoleInput.value.trim()) {
            window.AppFeedback.show(feedback, "Informe o cargo da pessoa não cadastrada.", "error");
            return;
        }

        const dataPrevista = dataPrevistaField.value;
        const horaPrevista = horaPrevistaField.value;
        if (!dataPrevista || !horaPrevista) {
            window.AppFeedback.show(feedback, "Informe a data e a hora previstas para a devolução.", "error");
            return;
        }
        if (!previsaoFuturaValida(dataPrevista, horaPrevista)) {
            window.AppFeedback.show(feedback, "A previsão de devolução precisa ser posterior ao horário atual.", "error");
            return;
        }

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        try {
            const key = availableKeys.find(function (item) {
                return String(item.id) === keySelect.value;
            });
            if (!key || !roomSelect.value || String(key.salaId) !== roomSelect.value) {
                window.AppFeedback.show(feedback, "Selecione uma chave disponível vinculada a uma sala.", "error");
                return;
            }
            await window.AppServices.keys.withdraw(
                key.id,
                borrowerType === "registered" ? userIdInput.value : "",
                borrowerType === "external" ? externalNameInput.value.trim() : "",
                borrowerType === "external" ? userRoleInput.value.trim() : "",
                document.getElementById("observacoes").value,
                {
                    date: dataPrevistaField.value,
                    time: horaPrevistaField.value
                },
                roomSelect.value
            );
            window.AppFeedback.show(feedback, "Retirada registrada com sucesso.", "success");
            submitButton.disabled = true;
            window.setTimeout(function () { window.location.href = "listadechaves.php"; }, 800);
        } catch (error) {
            window.AppFeedback.show(feedback, error.message || "Não foi possível registrar a retirada.", "error");
        }
    });

    const initialWithdrawalTime = new Date();
    setLocalDateTime(initialWithdrawalTime);
    setDefaultReturnForecast(initialWithdrawalTime);
    atualizarMinimoPrevisto();
    initialize();
});
}());
