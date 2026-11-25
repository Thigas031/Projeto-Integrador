(function () {
window.AppAccess.run(function () {
    const select = document.getElementById("return-key");
    const keySummary = document.getElementById("key-selection-summary");
    const feedback = document.getElementById("return-feedback");
    const form = document.getElementById("return-form");
    const submitButton = form.querySelector('[type="submit"]');
    const fields = {
        user: document.getElementById("return-user"),
        role: document.getElementById("return-role"),
        date: document.getElementById("return-date"),
        time: document.getElementById("return-time")
    };
    const requestedKey = new URLSearchParams(window.location.search).get("chave");
    let openKeys = [];
    let openMovements = [];

    function formatDate(value) {
        const date = new Date(value);
        return new Intl.DateTimeFormat("pt-BR", { dateStyle: "short" }).format(date);
    }

    function formatTime(value) {
        const date = new Date(value);
        return new Intl.DateTimeFormat("pt-BR", { timeStyle: "short" }).format(date);
    }

    function fillMovementDetails() {
        const key = openKeys.find(function (item) {
            return String(item.id) === select.value;
        });
        keySummary.replaceChildren();
        keySummary.hidden = !key;
        if (key) keySummary.appendChild(window.AppKeyDisplay.create(key));

        const movement = openMovements.find(function (item) {
            return String(item.keyId) === select.value;
        });
        fields.user.value = movement ? movement.userName : "";
        fields.role.value = movement ? window.AppServices.formatRole(movement.userRole) : "";
        fields.date.value = movement ? formatDate(movement.withdrawnAt) : "";
        fields.time.value = movement ? formatTime(movement.withdrawnAt) : "";
    }

    async function initialize() {
        try {
            const results = await Promise.all([
                window.AppServices.keys.listReturnable(),
                window.AppServices.movements.list()
            ]);
            openKeys = results[0];
            openMovements = results[1].filter(function (movement) { return !movement.returnedAt; });
            select.replaceChildren();
            const placeholder = document.createElement("option");
            placeholder.value = "";
            placeholder.textContent = openKeys.length ? "Selecione uma chave emprestada" : "Nenhuma chave emprestada";
            select.appendChild(placeholder);
            openKeys.forEach(function (key) {
                const option = document.createElement("option");
                option.value = key.id;
                option.textContent = window.AppKeyDisplay.optionLabel(key);
                select.appendChild(option);
            });

            if (requestedKey && openKeys.some(function (key) {
                return String(key.id) === requestedKey;
            })) {
                select.value = requestedKey;
            }
            submitButton.disabled = openKeys.length === 0;
            if (!openKeys.length) {
                window.AppFeedback.show(feedback, "Não há chaves emprestadas para devolver.", "error");
            }
            fillMovementDetails();
        } catch (error) {
            submitButton.disabled = true;
            window.AppFeedback.show(feedback, error.message || "Não foi possível carregar as devoluções.", "error");
        }
    }

    select.addEventListener("change", fillMovementDetails);
    document.querySelector("[data-cancel]").addEventListener("click", function () {
        window.location.href = "listadechaves.php";
    });
    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (!select.value) {
            window.AppFeedback.show(feedback, "Selecione uma chave emprestada para confirmar a devolução.", "error");
            return;
        }
        try {
            await window.AppServices.keys.returnKey(select.value);
            window.AppFeedback.show(feedback, "Devolução registrada com sucesso.", "success");
            submitButton.disabled = true;
            window.setTimeout(function () { window.location.href = "listadechaves.php"; }, 800);
        } catch (error) {
            window.AppFeedback.show(feedback, error.message || "Não foi possível registrar a devolução.", "error");
        }
    });

    initialize();
});
}());
