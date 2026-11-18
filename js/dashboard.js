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

    function addMovement(movement) {
        const row = document.createElement("article");
        row.className = "movement";
        const detail = document.createElement("div");
        const title = document.createElement("strong");
        const keyName = movement.keyName;
        const keyNumber = movement.keyNumber;
        title.textContent = keyName ? keyName + (keyNumber ? " — Nº " + keyNumber : "") : "Chave";
        const person = document.createElement("p");
        person.textContent = movement.userName || "Responsável não informado";
        const status = document.createElement("span");
        status.className = "movement-status";
        status.textContent = movement.status || (movement.returnedAt ? "Devolvida" : "Retirada");
        const date = document.createElement("time");
        date.dateTime = movement.returnedAt || movement.withdrawnAt;
        date.textContent = formatDate(movement.returnedAt || movement.withdrawnAt);
        detail.append(title, person);
        row.append(detail, status, date);
        movementList.appendChild(row);
    }

    async function loadSummary() {
        try {
            const summary = await window.AppServices.dashboard.summary();
            if (!summary || !summary.totais ||
                !Array.isArray(summary.ultimas_movimentacoes) ||
                !Array.isArray(summary.chaves_devolver_hoje)) {
                throw new Error("O servidor retornou um resumo do Dashboard inválido.");
            }
            const counts = summary.totais;

            document.getElementById("available").textContent = String(counts.Disponivel);
            document.getElementById("borrowed").textContent = String(counts.Emprestada);
            document.getElementById("late").textContent = String(counts.Atrasada);
            document.getElementById("due").textContent = String(summary.chaves_devolver_hoje.length);
            document.getElementById("due").nextElementSibling.textContent = "A devolver hoje";

            movementList.replaceChildren();
            if (!summary.ultimas_movimentacoes.length) {
                showState(movementList, "Nenhuma movimentação registrada.");
            } else {
                summary.ultimas_movimentacoes.slice(0, 10).forEach(function (movement) {
                    addMovement(movement);
                });
            }
            return null;
        } catch (error) {
            ["available", "borrowed", "late", "due"].forEach(function (id) {
                document.getElementById(id).textContent = "—";
            });
            showState(movementList, "Não foi possível carregar as movimentações porque o resumo do Dashboard não está disponível.", "error");
            return "Dashboard: " + (error.message || "Não foi possível carregar o resumo.");
        }
    }

    async function loadOccurrences() {
        try {
            const occurrences = await window.AppServices.occurrences.list();
            if (!Array.isArray(occurrences)) {
                throw new Error("O servidor retornou uma lista de ocorrências inválida.");
            }
            document.getElementById("occurrence-summary").textContent = occurrences.length
                ? occurrences.length + (occurrences.length === 1 ? " ocorrência registrada." : " ocorrências registradas.")
                : "Nenhuma ocorrência registrada.";
            return null;
        } catch (error) {
            document.getElementById("occurrence-summary").textContent =
                "Não foi possível carregar as ocorrências.";
            return "Ocorrências: " + (error.message || "Não foi possível carregar as ocorrências.");
        }
    }

    async function loadDashboard() {
        const errors = await Promise.all([loadSummary(), loadOccurrences()]);
        const messages = errors.filter(Boolean);
        if (messages.length) {
            window.AppFeedback.show(feedback, messages.join(" "), "error");
        } else {
            window.AppFeedback.clear(feedback);
        }
    }

    greeting.textContent = "Olá, " + user.name;
    loadDashboard();
});
}());
