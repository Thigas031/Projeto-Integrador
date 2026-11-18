(function () {
window.AppAccess.run(function (user) {
    const form = document.getElementById("occurrence-form");
    const tableBody = document.getElementById("occurrence-table-body");
    const feedback = document.getElementById("occurrence-feedback");
    const formFeedback = document.getElementById("occurrence-form-feedback");

    function render(occurrences) {
        tableBody.replaceChildren();
        if (!occurrences.length) {
            const row = document.createElement("tr");
            const cell = document.createElement("td");
            cell.colSpan = 3;
            cell.textContent = "Nenhuma ocorrência registrada.";
            row.appendChild(cell);
            tableBody.appendChild(row);
            return;
        }

        occurrences.forEach(function (occurrence) {
            const row = document.createElement("tr");
            const date = document.createElement("td");
            date.textContent = new Intl.DateTimeFormat("pt-BR", {
                dateStyle: "short",
                timeStyle: "short"
            }).format(new Date(occurrence.createdAt));
            const author = document.createElement("td");
            author.textContent = occurrence.createdBy || "—";
            const description = document.createElement("td");
            description.textContent = occurrence.text;
            row.append(date, author, description);
            tableBody.appendChild(row);
        });
    }

    async function loadOccurrences() {
        tableBody.replaceChildren();
        const row = document.createElement("tr");
        const cell = document.createElement("td");
        cell.colSpan = 3;
        cell.textContent = "Carregando ocorrências...";
        row.appendChild(cell);
        tableBody.appendChild(row);
        try {
            render(await window.AppServices.occurrences.list());
            window.AppFeedback.clear(feedback);
        } catch (error) {
            tableBody.replaceChildren();
            const errorRow = document.createElement("tr");
            const errorCell = document.createElement("td");
            errorCell.colSpan = 3;
            errorCell.textContent = "Não foi possível carregar as ocorrências.";
            errorRow.appendChild(errorCell);
            tableBody.appendChild(errorRow);
            window.AppFeedback.show(feedback, error.message || "Não foi possível carregar as ocorrências.", "error");
        }
    }

    form.hidden = !window.AppProfile.isAllowed("PORTEIRO");
    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        const text = document.getElementById("occurrence-text").value.trim();
        if (!form.checkValidity() || !text) {
            form.reportValidity();
            window.AppFeedback.show(formFeedback, "Informe a descrição da ocorrência.", "error");
            return;
        }
        try {
            await window.AppServices.occurrences.create({ text: text });
            document.getElementById("occurrence-text").value = "";
            window.AppFeedback.show(formFeedback, "Ocorrência registrada com sucesso.", "success");
            await loadOccurrences();
        } catch (error) {
            window.AppFeedback.show(formFeedback, error.message || "Não foi possível registrar a ocorrência.", "error");
        }
    });

    loadOccurrences();
});
}());
