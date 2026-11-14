(function () {
window.AppAccess.run(function () {
    const form = document.getElementById("withdrawal-form");
    const feedback = document.getElementById("withdrawal-feedback");
    const roomSelect = document.getElementById("sala");
    const userSelect = document.getElementById("usuario");
    const keySelect = document.getElementById("chave");
    const submitButton = form.querySelector('[type="submit"]');
    const requestedKey = new URLSearchParams(window.location.search).get("chave");
    let availableKeys = [];

    function setLocalDateTime() {
        const now = new Date();
        const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
        const valorLocal = local.toISOString();
        document.getElementById("data").value = valorLocal.slice(0, 10);
        document.getElementById("hora").value = valorLocal.slice(11, 16);
    }

    function atualizarMinimoPrevisto() {
        const dataPrevista = document.getElementById("data-prevista");
        const horaPrevista = document.getElementById("hora-prevista");
        const agora = new Date();
        const localAgora = new Date(agora.getTime() - agora.getTimezoneOffset() * 60000);
        const limiteHora = new Date(agora.getTime());
        limiteHora.setSeconds(0, 0);
        limiteHora.setMinutes(limiteHora.getMinutes() + 2);
        const localLimite = new Date(limiteHora.getTime() - limiteHora.getTimezoneOffset() * 60000);

        dataPrevista.min = localAgora.toISOString().slice(0, 10);
        if (dataPrevista.value === dataPrevista.min) {
            horaPrevista.min = localLimite.toISOString().slice(11, 16);
        } else {
            horaPrevista.removeAttribute("min");
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

    async function initialize() {
        try {
            const options = await window.AppServices.movements.options();
            availableKeys = options.chaves;
            const users = options.usuarios;
            populate(roomSelect, "Selecione uma sala", options.salas, function (room) {
                return room.name + " — Nº " + room.numero + " — Bloco " + room.bloco;
            });
            populate(userSelect, users.length ? "Selecione o usuário cadastrado" : "Nenhum usuário cadastrado", users,
                function (user) { return user.name; });

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

            submitButton.disabled = !availableKeys.length || !users.length;
            if (!availableKeys.length) {
                window.AppFeedback.show(feedback, "Não há chaves disponíveis para retirada.", "error");
            } else if (!users.length) {
                window.AppFeedback.show(feedback, "Cadastre o usuário responsável antes de registrar a retirada.", "error");
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
            return key.name + " — Nº " + key.number;
        });
        keySelect.disabled = !chavesDaSala.length;
        if (idChaveSelecionada !== undefined) {
            keySelect.value = String(idChaveSelecionada);
        }
    }

    roomSelect.addEventListener("change", function () {
        carregarChaves(roomSelect.value);
    });
    document.getElementById("data-prevista").addEventListener("change", atualizarMinimoPrevisto);
    keySelect.addEventListener("change", function () {
        if (!keySelect.value) return;
        const key = availableKeys.find(function (item) {
            return String(item.id) === keySelect.value;
        });
        if (key && String(key.salaId) !== roomSelect.value) {
            roomSelect.value = String(key.salaId);
            carregarChaves(roomSelect.value, key.id);
        }
    });
    form.querySelector("[data-cancel]").addEventListener("click", function () {
        window.location.href = "listadechaves.php";
    });

    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (!roomSelect.value || !keySelect.value || !userSelect.value) {
            window.AppFeedback.show(feedback, "Selecione uma sala, uma chave disponível e o usuário responsável.", "error");
            return;
        }

        const dataPrevista = document.getElementById("data-prevista").value;
        const horaPrevista = document.getElementById("hora-prevista").value;
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
            setLocalDateTime();
            const key = availableKeys.find(function (item) {
                return String(item.id) === keySelect.value;
            });
            if (!key || !roomSelect.value || String(key.salaId) !== roomSelect.value) {
                window.AppFeedback.show(feedback, "Selecione uma chave disponível vinculada a uma sala.", "error");
                return;
            }
            await window.AppServices.keys.withdraw(
                key.id,
                userSelect.value,
                document.getElementById("observacoes").value,
                {
                    date: document.getElementById("data-prevista").value,
                    time: document.getElementById("hora-prevista").value
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

    setLocalDateTime();
    atualizarMinimoPrevisto();
    initialize();
});
}());
