(function () {
    const search = document.getElementById("busca");
    const rows = Array.from(document.querySelectorAll("#corpo-tabela tr:not(#linha-sem-resultados)"));
    const returnButton = document.getElementById("devolver-chave");
    const newKeyButton = document.getElementById("nova-chave");
    const quantity = document.getElementById("quantidade");
    const pagination = document.querySelector(".paginacao");
    const pageNumbers = document.getElementById("paginas");
    const previousButton = document.querySelector('[data-page-action="previous"]');
    const nextButton = document.querySelector('[data-page-action="next"]');
    const emptyRow = document.getElementById("linha-sem-resultados");
    const pageSize = 5;
    let currentPage = 1;
    const statusLabels = {
        disponivel: "Disponível",
        emprestada: "Emprestada",
        atrasada: "Atrasada"
    };

    function syncDemoStatuses() {
        const demoKeys = window.DemoKeys.read().keys;

        rows.forEach(function (row) {
            const keyNumber = row.cells[1].textContent.trim();
            const key = demoKeys.find(function (item) {
                return item.id === keyNumber;
            });
            const statusLabel = key && statusLabels[key.status];

            if (!key || !statusLabel) return;

            const statusBadge = row.querySelector("td:nth-child(3) span");
            statusBadge.className = key.status;
            statusBadge.textContent = statusLabel;
            row.cells[3].textContent = key.user || "-";
        });
    }

    function filteredRows() {
        const term = search.value.trim().toLocaleLowerCase("pt-BR");

        return rows.filter(function (row) {
            return row.textContent.toLocaleLowerCase("pt-BR").includes(term);
        });
    }

    function renderTable() {
        const matches = filteredRows();
        const pageCount = Math.ceil(matches.length / pageSize);
        currentPage = Math.min(currentPage, Math.max(pageCount, 1));

        const firstIndex = (currentPage - 1) * pageSize;
        const visibleRows = new Set(matches.slice(firstIndex, firstIndex + pageSize));

        rows.forEach(function (row) {
            row.hidden = !visibleRows.has(row);
        });

        emptyRow.hidden = matches.length > 0;
        quantity.textContent = matches.length
            ? `Mostrando ${firstIndex + 1} a ${Math.min(firstIndex + pageSize, matches.length)} chaves de ${matches.length}`
            : "Nenhuma chave encontrada.";

        pageNumbers.replaceChildren();
        for (let page = 1; page <= pageCount; page += 1) {
            const button = document.createElement("button");
            button.type = "button";
            button.textContent = String(page);
            button.dataset.page = String(page);
            if (page === currentPage) {
                button.setAttribute("aria-current", "page");
            }
            pageNumbers.appendChild(button);
        }

        previousButton.disabled = pageCount === 0 || currentPage === 1;
        nextButton.disabled = pageCount === 0 || currentPage === pageCount;
    }

    function openReturnPage() {
        window.location.href = "devolucaodechaves.html";
    }

    function openNewKeyPage() {
        window.location.href = "cadastrodechaves.html";
    }

    function openKeyAction(event) {
        const button = event.target.closest("[data-key-action]");
        if (!button) return;

        const row = button.closest("tr");
        const keyNumber = row.cells[1].textContent.trim();
        const status = row.querySelector("td:nth-child(3) span");

        if (!status || !["disponivel", "emprestada", "atrasada"].some(function (name) {
            return status.classList.contains(name);
        })) {
            return;
        }

        const destination = status.classList.contains("disponivel")
            ? "retiradadechaves.html"
            : "devolucaodechaves.html";
        window.location.href = `${destination}?chave=${encodeURIComponent(keyNumber)}`;
    }

    search.addEventListener("input", function () {
        currentPage = 1;
        renderTable();
    });

    pagination.addEventListener("click", function (event) {
        const pageButton = event.target.closest("button[data-page]");
        if (!pageButton) return;

        currentPage = Number(pageButton.dataset.page);
        renderTable();
    });

    previousButton.addEventListener("click", function () {
        currentPage -= 1;
        renderTable();
    });

    nextButton.addEventListener("click", function () {
        currentPage += 1;
        renderTable();
    });

    document.getElementById("tabela-chaves").addEventListener("click", openKeyAction);

    if (returnButton) {
        returnButton.addEventListener("click", openReturnPage);
    }

    if (newKeyButton) {
        newKeyButton.addEventListener("click", openNewKeyPage);
    }

    syncDemoStatuses();
    renderTable();
}());
