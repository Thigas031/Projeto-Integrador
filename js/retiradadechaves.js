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
        document.getElementById("data").value = local.toISOString().slice(0, 10);
        document.getElementById("hora").value = local.toISOString().slice(11, 16);
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
            const results = await Promise.all([
                window.AppServices.keys.list(),
                window.AppServices.users.listForMovement()
            ]);
            availableKeys = results[0].filter(function (key) { return key.status === "Disponivel"; });
            const users = results[1];
            populate(keySelect, availableKeys.length ? "Selecione uma chave disponível" : "Nenhuma chave disponível", availableKeys,
                function (key) { return key.name + " — Nº " + key.number; });
            populate(userSelect, users.length ? "Selecione o usuário cadastrado" : "Nenhum usuário cadastrado", users,
                function (user) { return user.name; });

            if (requestedKey && availableKeys.some(function (key) { return key.id === requestedKey; })) {
                keySelect.value = requestedKey;
                roomSelect.value = availableKeys.find(function (key) { return key.id === requestedKey; }).name;
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

    keySelect.addEventListener("change", function () {
        const key = availableKeys.find(function (item) { return item.id === keySelect.value; });
        roomSelect.value = key ? key.name : "";
    });
    form.querySelector("[data-cancel]").addEventListener("click", function () {
        window.location.href = "listadechaves.php";
    });

    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (!form.checkValidity()) {
            form.reportValidity();
            window.AppFeedback.show(feedback, "Selecione uma chave disponível e o usuário responsável.", "error");
            return;
        }

        try {
            setLocalDateTime();
            await window.AppServices.keys.withdraw(
                keySelect.value,
                userSelect.value,
                document.getElementById("observacoes").value
            );
            window.AppFeedback.show(feedback, "Retirada registrada com sucesso.", "success");
            submitButton.disabled = true;
            window.setTimeout(function () { window.location.href = "listadechaves.php"; }, 800);
        } catch (error) {
            window.AppFeedback.show(feedback, error.message || "Não foi possível registrar a retirada.", "error");
        }
    });

    setLocalDateTime();
    initialize();
});
}());
