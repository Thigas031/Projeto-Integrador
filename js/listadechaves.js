(function () {
window.AppAccess.run(function (user) {

    const profile = user;
    const search = document.getElementById("busca");
    const body = document.getElementById("corpo-tabela");
    const emptyRow = document.getElementById("linha-sem-resultados");
    const quantity = document.getElementById("quantidade");
    const pageNumbers = document.getElementById("paginas");
    const previousButton = document.querySelector('[data-page-action="previous"]');
    const nextButton = document.querySelector('[data-page-action="next"]');
    const feedback = document.getElementById("key-list-feedback");
    const dialog = document.getElementById("manager-movement-dialog");
    const movementForm = document.getElementById("manager-movement-form");
    const movementUser = document.getElementById("manager-movement-user");
    const movementFeedback = document.getElementById("manager-movement-feedback");
    const quickActions = document.querySelector(".acoes-rapidas");
    const statusLabels = {
        Disponivel: "Disponível",
        Emprestada: "Emprestada",
        Atrasada: "Atrasada"
    };
    const pageSize = 5;
    let keys = [];
    let totalKeys = 0;
    let pageCount = 0;
    let currentPage = 1;
    let movementKeyId = "";
    let searchTimeout = null;
    const dueFields = document.createElement("div");
    const dueDateLabel = document.createElement("label");
    const dueDate = document.createElement("input");
    const dueTimeLabel = document.createElement("label");
    const dueTime = document.createElement("input");

    dueFields.className = "manager-due-fields";
    dueDateLabel.textContent = "Data prevista de devolução";
    dueDateLabel.htmlFor = "manager-movement-due-date";
    dueDate.type = "date";
    dueDate.id = "manager-movement-due-date";
    dueDate.required = true;
    dueTimeLabel.textContent = "Hora prevista de devolução";
    dueTimeLabel.htmlFor = "manager-movement-due-time";
    dueTime.type = "time";
    dueTime.id = "manager-movement-due-time";
    dueTime.required = true;
    dueFields.append(dueDateLabel, dueDate, dueTimeLabel, dueTime);
    movementForm.insertBefore(dueFields, movementForm.querySelector(".dialog-actions"));

    function setFeedback(element, message, type) {
        if (!message) {
            window.AppFeedback.clear(element);
            return;
        }
        window.AppFeedback.show(element, message, type);
    }

    function button(label, action, keyId, className) {
        const element = document.createElement("button");
        element.type = "button";
        element.textContent = label;
        element.dataset.action = action;
        element.dataset.keyId = keyId;
        if (className) element.className = className;
        return element;
    }

    function renderActions(key, cell) {
        if (profile.role === "GERENTE") {
            cell.append(
                button("Editar", "edit", key.id),
                button(key.status === "Disponivel" ? "Movimentar" : "Registrar devolução", "move", key.id),
                button("Desativar", "delete", key.id, "danger")
            );
            return;
        }

        const action = key.status === "Disponivel" ? "withdraw" : "return";
        cell.append(button(key.status === "Disponivel" ? "Retirar" : "Devolver", action, key.id));
    }

    function renderTable() {
        pageCount = Math.ceil(totalKeys / pageSize);
        currentPage = Math.min(currentPage, Math.max(pageCount, 1));
        const firstIndex = (currentPage - 1) * pageSize;

        body.replaceChildren();
        if (!keys.length) {
            emptyRow.hidden = false;
            emptyRow.cells[0].textContent = search.value.trim()
                ? "Nenhuma chave encontrada."
                : "Nenhuma chave cadastrada.";
            body.appendChild(emptyRow);
        } else {
            emptyRow.hidden = true;
            keys.forEach(function (key) {
                const row = document.createElement("tr");
                row.dataset.keyId = key.id;
                const name = document.createElement("td");
                name.textContent = key.name;
                const number = document.createElement("td");
                number.textContent = "Nº " + key.number;
                const status = document.createElement("td");
                const badge = document.createElement("span");
                badge.className = String(key.status || "").toLowerCase();
                badge.textContent = statusLabels[key.status] || "Indisponível";
                status.appendChild(badge);
                const responsible = document.createElement("td");
                responsible.textContent = key.responsible || "—";
                const actions = document.createElement("td");
                actions.className = "key-actions";
                renderActions(key, actions);
                row.append(name, number, status, responsible, actions);
                body.appendChild(row);
            });
        }

        quantity.textContent = totalKeys
            ? "Mostrando " + (firstIndex + 1) + " a " + Math.min(firstIndex + keys.length, totalKeys) + " chaves de " + totalKeys
            : search.value.trim() ? "Nenhuma chave encontrada." : "Nenhuma chave cadastrada.";
        pageNumbers.replaceChildren();
        for (let page = 1; page <= pageCount; page += 1) {
            const pageButton = document.createElement("button");
            pageButton.type = "button";
            pageButton.textContent = String(page);
            pageButton.dataset.page = String(page);
            if (page === currentPage) pageButton.setAttribute("aria-current", "page");
            pageNumbers.appendChild(pageButton);
        }
        previousButton.disabled = pageCount === 0 || currentPage === 1;
        nextButton.disabled = pageCount === 0 || currentPage === pageCount;
    }

    function openPage(file, id) {
        const query = id ? "?chave=" + encodeURIComponent(id) : "";
        window.location.href = file + query;
    }

    async function openManagerMovement(key) {
        movementKeyId = key.id;
        document.getElementById("manager-movement-key").textContent =
            key.name + " — Nº " + key.number + " (" + (statusLabels[key.status] || key.status) + ")";
        setFeedback(movementFeedback, "", "error");
        movementUser.replaceChildren();
        const placeholder = document.createElement("option");
        placeholder.value = "";
        placeholder.textContent = "Selecione um usuário cadastrado";
        movementUser.appendChild(placeholder);

        const isWithdrawal = key.status === "Disponivel";
        movementUser.hidden = !isWithdrawal;
        movementUser.required = isWithdrawal;
        dueFields.hidden = !isWithdrawal;
        dueDate.required = isWithdrawal;
        dueTime.required = isWithdrawal;
        dueDate.value = "";
        dueTime.value = "";
        if (isWithdrawal) {
            const users = await window.AppServices.users.listForMovement();
            users.forEach(function (user) {
                const option = document.createElement("option");
                option.value = user.id;
                option.textContent = user.name;
                movementUser.appendChild(option);
            });
            if (!users.length) {
                setFeedback(movementFeedback, "Cadastre um usuário na área Usuários antes de registrar uma retirada.", "error");
            }
        }
        dialog.showModal();
    }

    async function handleAction(event) {
        const actionButton = event.target.closest("button[data-action]");
        if (!actionButton) return;
        const key = keys.find(function (item) {
            return String(item.id) === actionButton.dataset.keyId;
        });
        if (!key) return;

        try {
            switch (actionButton.dataset.action) {
                case "edit":
                    window.location.href = "cadastrodechaves.php?id=" + encodeURIComponent(key.id);
                    return;
                case "move":
                    await openManagerMovement(key);
                    return;
                case "delete":
                    if (!window.confirm("Deseja desativar a chave " + key.name + " — Nº " + key.number + "?")) return;
                    await window.AppServices.keys.delete(key.id);
                    setFeedback(feedback, "Chave desativada. O histórico de movimentações foi preservado.", "success");
                    await loadKeys(true);
                    return;
                case "withdraw":
                    openPage("retiradadechaves.php", key.id);
                    return;
                case "return":
                    openPage("devolucaodechaves.php", key.id);
                    return;
                default:
                    return;
            }
        } catch (error) {
            setFeedback(feedback, error.message || "Não foi possível executar a ação.", "error");
        }
    }

    async function loadKeys(preserveFeedback) {
        body.replaceChildren();
        const loading = document.createElement("tr");
        loading.innerHTML = '<td colspan="5">Carregando chaves...</td>';
        body.appendChild(loading);
        try {
            const resultado = await window.AppServices.keys.listPage({
                busca: search.value.trim(),
                pagina: currentPage
            });
            const ultimaPagina = Math.max(1, Math.ceil(resultado.total / pageSize));
            if (resultado.total > 0 && currentPage > ultimaPagina) {
                currentPage = ultimaPagina;
                await loadKeys(preserveFeedback);
                return;
            }
            keys = resultado.items;
            totalKeys = resultado.total;
            currentPage = resultado.page;
            if (!preserveFeedback) setFeedback(feedback, "", "error");
            renderTable();
        } catch (error) {
            body.replaceChildren();
            const row = document.createElement("tr");
            const cell = document.createElement("td");
            cell.colSpan = 5;
            cell.textContent = "Não foi possível carregar as chaves.";
            row.appendChild(cell);
            body.appendChild(row);
            setFeedback(feedback, error.message || "Não foi possível carregar as chaves.", "error");
        }
    }

    document.getElementById("nova-chave").addEventListener("click", function () {
        window.location.href = "cadastrodechaves.php";
    });
    document.getElementById("nova-chave").hidden = profile.role !== "GERENTE";
    document.getElementById("retirar-chave").addEventListener("click", function () {
        if (profile.role === "PORTEIRO") window.location.href = "retiradadechaves.php";
    });
    document.getElementById("devolver-chave").addEventListener("click", function () {
        if (profile.role === "PORTEIRO") window.location.href = "devolucaodechaves.php";
    });
    if (profile.role !== "PORTEIRO") quickActions.hidden = true;
    document.querySelectorAll("[data-profiles]").forEach(function (element) {
        element.hidden = !window.AppProfile.isAllowed(element.dataset.profiles);
    });

    search.addEventListener("input", function () {
        currentPage = 1;
        window.clearTimeout(searchTimeout);
        searchTimeout = window.setTimeout(function () { loadKeys(); }, 250);
    });
    document.getElementById("form-busca").addEventListener("submit", function (event) {
        event.preventDefault();
    });
    document.querySelector(".paginacao").addEventListener("click", function (event) {
        const pageButton = event.target.closest("button[data-page]");
        if (pageButton) {
            currentPage = Number(pageButton.dataset.page);
            loadKeys();
        }
    });
    previousButton.addEventListener("click", function () { currentPage -= 1; loadKeys(); });
    nextButton.addEventListener("click", function () { currentPage += 1; loadKeys(); });
    document.getElementById("tabela-chaves").addEventListener("click", handleAction);
    document.querySelector("[data-manager-movement-cancel]").addEventListener("click", function () {
        dialog.close();
    });
    movementForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        const key = keys.find(function (item) {
            return String(item.id) === String(movementKeyId);
        });
        if (!key) return;
        try {
            if (key.status === "Disponivel") {
                if (!dueDate.value || !dueTime.value || !movementUser.value) {
                    window.AppFeedback.show(movementFeedback, "Informe usuário e previsão de devolução.", "error");
                    return;
                }
                await window.AppServices.keys.withdraw(
                    key.id,
                    movementUser.value,
                    "",
                    { date: dueDate.value, time: dueTime.value },
                    key.salaId
                );
                setFeedback(feedback, "Retirada registrada com sucesso.", "success");
            } else {
                await window.AppServices.keys.returnKey(key.id);
                setFeedback(feedback, "Devolução registrada com sucesso.", "success");
            }
            dialog.close();
            await loadKeys(true);
        } catch (error) {
            setFeedback(movementFeedback, error.message || "Não foi possível movimentar a chave.", "error");
        }
    });

    loadKeys();
});
}());
