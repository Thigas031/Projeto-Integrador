(function () {
window.AppAccess.run(function (user) {
    const feedback = document.getElementById("dashboard-feedback");
    const movementList = document.getElementById("movements");
    const greeting = document.getElementById("dashboard-greeting");
    const overduePanel = document.querySelector(".overdue-panel");
    const overdueCount = document.getElementById("overdue-count");
    const overdueList = document.getElementById("overdue-list");
    let dashboardLoading = false;

    function showState(container, message, type) {
        container.replaceChildren();
        const state = document.createElement("p");
        state.className = "data-state" + (type ? " " + type : "");
        state.textContent = message;
        container.appendChild(state);
    }

    function formatDate(value) {
        const normalized = String(value).replace(" ", "T");
        return new Intl.DateTimeFormat("pt-BR", {
            dateStyle: "short",
            timeStyle: "short"
        }).format(new Date(normalized));
    }

    function formatDuration(value) {
        const totalMinutes = Math.floor(Math.max(0, Number(value) || 0) / 60);
        const days = Math.floor(totalMinutes / 1440);
        const hours = Math.floor((totalMinutes % 1440) / 60);
        const minutes = totalMinutes % 60;
        const parts = [];
        if (days) parts.push(days + (days === 1 ? " dia" : " dias"));
        if (hours) parts.push(hours + (hours === 1 ? " hora" : " horas"));
        if (minutes || !parts.length) parts.push(minutes + (minutes === 1 ? " minuto" : " minutos"));
        return parts.join(" e ");
    }

    function addOverdue(movement) {
        const row = document.createElement("article");
        row.className = "overdue-item";
        const details = document.createElement("div");
        details.className = "overdue-details";
        details.appendChild(window.AppKeyDisplay.create(Object.assign({}, movement, {
            keyName: movement.keyName || "Chave"
        })));

        const person = document.createElement("p");
        person.className = "overdue-meta";
        person.textContent = (movement.roomName || "Sala não informada") + " · " +
            (movement.userName || "Responsável não informado");
        const due = document.createElement("p");
        due.className = "overdue-meta";
        due.textContent = "Devolução prevista: " + formatDate(movement.dueAt);
        details.append(person, due);

        const actions = document.createElement("div");
        actions.className = "overdue-actions";
        const delay = document.createElement("p");
        delay.className = "overdue-delay";
        delay.textContent = "Atrasada há " + formatDuration(movement.lateSeconds);
        actions.appendChild(delay);
        if (window.AppProfile.isAllowed("PORTEIRO")) {
            const returnLink = document.createElement("a");
            returnLink.className = "overdue-return";
            returnLink.href = "devolucaodechaves.php?chave=" + encodeURIComponent(movement.keyId);
            returnLink.textContent = "Registrar devolução";
            actions.appendChild(returnLink);
        }

        row.append(details, actions);
        overdueList.appendChild(row);
    }

    function renderOverdue(movements) {
        overdueCount.textContent = String(movements.length);
        overduePanel.classList.toggle("is-clear", movements.length === 0);
        overdueList.replaceChildren();
        if (!movements.length) {
            showState(overdueList, "Nenhuma chave atrasada no momento.");
            return;
        }
        movements.forEach(addOverdue);
    }

    function addMovement(movement) {
        const row = document.createElement("article");
        row.className = "movement";
        const detail = document.createElement("div");
        const keyIdentity = window.AppKeyDisplay.create(Object.assign({}, movement, {
            keyName: movement.keyName || "Chave"
        }));
        const person = document.createElement("p");
        person.className = "movement-person";
        person.textContent = movement.userName
            ? movement.userName + (movement.userRole ? " — " + movement.userRole : "")
            : "Responsável não informado";
        const status = document.createElement("span");
        status.className = "movement-status";
        status.textContent = movement.status || (movement.returnedAt ? "Devolvida" : "Retirada");
        const date = document.createElement("time");
        date.dateTime = movement.returnedAt || movement.withdrawnAt;
        date.textContent = formatDate(movement.returnedAt || movement.withdrawnAt);
        detail.className = "movement-detail";
        detail.append(keyIdentity, person);
        row.append(detail, status, date);
        movementList.appendChild(row);
    }

    async function loadSummary() {
        try {
            const summary = await window.AppServices.dashboard.summary();
            if (!summary || !summary.totais ||
                !Array.isArray(summary.ultimas_movimentacoes) ||
                !Array.isArray(summary.chaves_devolver_hoje) ||
                !Array.isArray(summary.atrasadas)) {
                throw new Error("O servidor retornou um resumo do Dashboard inválido.");
            }
            const counts = summary.totais;

            document.getElementById("available").textContent = String(counts.Disponivel);
            document.getElementById("borrowed").textContent = String(counts.Emprestada);
            document.getElementById("late").textContent = String(counts.Atrasada);
            document.getElementById("due").textContent = String(summary.chaves_devolver_hoje.length);
            document.getElementById("due").nextElementSibling.textContent = "A devolver hoje";
            renderOverdue(summary.atrasadas);

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
            overdueCount.textContent = "—";
            overduePanel.classList.remove("is-clear");
            showState(overdueList, "Não foi possível consultar os atrasos.", "error");
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
        if (dashboardLoading) return;
        dashboardLoading = true;
        try {
            const errors = await Promise.all([loadSummary(), loadOccurrences()]);
            const messages = errors.filter(Boolean);
            if (messages.length) {
                window.AppFeedback.show(feedback, messages.join(" "), "error");
            } else {
                window.AppFeedback.clear(feedback);
            }
        } finally {
            dashboardLoading = false;
        }
    }

    greeting.textContent = "Olá, " + user.name;
    loadDashboard();
    window.setInterval(loadDashboard, 60000);
    window.addEventListener("focus", loadDashboard);
});
}());
