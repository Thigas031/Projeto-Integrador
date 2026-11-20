(function () {
window.AppAccess.run(function () {
    const search = document.getElementById("busca");
    const body = document.getElementById("corpo-tabela");
    const emptyRow = document.getElementById("linha-sem-resultados");
    const quantity = document.getElementById("quantidade");
    const pageNumbers = document.getElementById("paginas");
    const previousButton = document.querySelector('[data-page-action="previous"]');
    const nextButton = document.querySelector('[data-page-action="next"]');
    const feedback = document.getElementById("key-list-feedback");
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
    let searchTimeout = null;

    function setFeedback(element, message, type) {
        if (!message) {
            window.AppFeedback.clear(element);
            return;
        }
        window.AppFeedback.show(element, message, type);
    }

    function button(label, action, keyId, className, icon) {
        const element = document.createElement("button");
        element.type = "button";
        if (icon) {
            const image = document.createElement("img");
            image.className = "key-action-icon";
            image.src = icon === "withdraw"
                ? "../assets/icons/retirar chave.svg"
                : "../assets/icons/devolver chave.svg";
            image.alt = "";
            element.appendChild(image);
        }
        const text = document.createElement("span");
        text.textContent = label;
        element.appendChild(text);
        element.dataset.action = action;
        element.dataset.keyId = keyId;
        if (className) element.className = className;
        return element;
    }

    function renderActions(key, cell) {
        if (window.AppProfile.isAllowed("GERENTE")) {
            cell.append(
                button("Editar", "edit", key.id),
                button("Desativar", "delete", key.id, "danger")
            );
        }

        if (window.AppProfile.isAllowed("PORTEIRO")) {
            const action = key.status === "Disponivel" ? "withdraw" : "return";
            cell.append(button(key.status === "Disponivel" ? "Retirar" : "Devolver", action, key.id, "", action));
        }
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
                name.appendChild(window.AppKeyDisplay.create(key));
                const status = document.createElement("td");
                const badge = document.createElement("span");
                badge.className = String(key.status || "").toLowerCase();
                badge.textContent = statusLabels[key.status] || "Indisponível";
                status.appendChild(badge);
                const responsible = document.createElement("td");
                responsible.textContent = key.responsible
                    ? key.responsible + (key.responsibleRole ? " — " + key.responsibleRole : "")
                    : "—";
                const actions = document.createElement("td");
                actions.className = "key-actions";
                renderActions(key, actions);
                row.append(name, status, responsible, actions);
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
                    window.location.href = "editarchave.php?id=" + encodeURIComponent(key.id);
                    return;
                case "delete":
                    if (!window.confirm("Deseja desativar a chave " + window.AppKeyDisplay.optionLabel(key) + "?")) return;
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
        loading.innerHTML = '<td colspan="4">Carregando chaves...</td>';
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
            cell.colSpan = 4;
            cell.textContent = "Não foi possível carregar as chaves.";
            row.appendChild(cell);
            body.appendChild(row);
            setFeedback(feedback, error.message || "Não foi possível carregar as chaves.", "error");
        }
    }

    document.getElementById("nova-chave").addEventListener("click", function () {
        window.location.href = "cadastrodechaves.php";
    });
    document.getElementById("retirar-chave").addEventListener("click", function () {
        if (window.AppProfile.isAllowed("PORTEIRO")) window.location.href = "retiradadechaves.php";
    });
    document.getElementById("devolver-chave").addEventListener("click", function () {
        if (window.AppProfile.isAllowed("PORTEIRO")) window.location.href = "devolucaodechaves.php";
    });
    if (!window.AppProfile.isAllowed("PORTEIRO")) quickActions.hidden = true;
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

    loadKeys();
});
}());
