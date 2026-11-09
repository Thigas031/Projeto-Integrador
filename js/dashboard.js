(function () {
window.AppAccess.run(function (user) {
    const feedback = document.getElementById("dashboard-feedback");
    const movementList = document.getElementById("movements");
    const greeting = document.getElementById("dashboard-greeting");

    function showState(container, message, type) {
        container.replaceChildren();
        const state = document.createElement("p");
        state.className = "data-state" + (type ? " " + type : "");
        state.textContent = message;
        container.appendChild(state);
    }

    function formatDate(value) {
        return new Intl.DateTimeFormat("pt-BR", {
            dateStyle: "short",
            timeStyle: "short"
        }).format(new Date(value));
    }

    function addMovement(movement, keys) {
        const key = keys.find(function (item) { return item.id === movement.keyId; });
        const row = document.createElement("article");
        row.className = "movement";
        const detail = document.createElement("div");
        const title = document.createElement("strong");
        const keyName = key ? key.name : movement.keyName;
        const keyNumber = key ? key.number : movement.keyNumber;
        title.textContent = keyName ? keyName + (keyNumber ? " — Nº " + keyNumber : "") : "Chave";
        const person = document.createElement("p");
        person.textContent = movement.userName || "Responsável não informado";
        const status = document.createElement("span");
        status.className = "movement-status";
        status.textContent = movement.returnedAt ? "Devolvida" : "Retirada";
        const date = document.createElement("time");
        date.dateTime = movement.returnedAt || movement.withdrawnAt;
        date.textContent = formatDate(movement.returnedAt || movement.withdrawnAt);
        detail.append(title, person);
        row.append(detail, status, date);
        movementList.appendChild(row);
    }

    async function loadDashboard() {
        try {
            const results = await Promise.all([
                window.AppServices.keys.list(),
                window.AppServices.movements.list(),
                window.AppServices.occurrences.list()
            ]);
            const keys = results[0];
            const movements = results[1];
            const occurrences = results[2];
            const counts = { Disponivel: 0, Emprestada: 0, Atrasada: 0 };
            keys.forEach(function (key) {
                if (Object.prototype.hasOwnProperty.call(counts, key.status)) {
                    counts[key.status] += 1;
                }
            });

            document.getElementById("available").textContent = String(counts.Disponivel);
            document.getElementById("borrowed").textContent = String(counts.Emprestada);
            document.getElementById("late").textContent = String(counts.Atrasada);
            document.getElementById("due").textContent = String(counts.Emprestada);
            document.getElementById("occurrence-summary").textContent = occurrences.length
                ? occurrences.length + (occurrences.length === 1 ? " ocorrência registrada." : " ocorrências registradas.")
                : "Nenhuma ocorrência registrada.";

            movementList.replaceChildren();
            if (!movements.length) {
                showState(movementList, "Nenhuma movimentação registrada.");
            } else {
                movements.slice(0, 5).forEach(function (movement) {
                    addMovement(movement, keys);
                });
            }
        } catch (error) {
            ["available", "borrowed", "late", "due"].forEach(function (id) {
                document.getElementById(id).textContent = "—";
            });
            document.getElementById("occurrence-summary").textContent =
                "Não foi possível carregar as ocorrências.";
            window.AppFeedback.show(feedback, error.message || "Não foi possível carregar os dados do Dashboard.", "error");
            showState(movementList, "Não foi possível carregar as movimentações.", "error");
        }
    }

    greeting.textContent = "Olá, " + user.name;
    loadDashboard();
});
}());
