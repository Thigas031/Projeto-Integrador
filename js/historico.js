(function () {
window.AppAccess.run(function () {
    const form = document.getElementById("history-filter-form");
    const startDate = document.getElementById("history-start");
    const endDate = document.getElementById("history-end");
    const roomSelect = document.getElementById("history-room");
    const userSelect = document.getElementById("history-user");
    const statusSelect = document.getElementById("history-status");
    const rowsContainer = document.getElementById("history-rows");
    const countLabel = document.getElementById("history-count");
    const feedback = document.getElementById("history-feedback");
    const exportButton = document.getElementById("history-export");
    let appliedFilters = {};

    function formatDateTime(value) {
        if (!value) return "—";
        const parts = String(value).split(" ");
        const date = (parts[0] || "").split("-");
        if (date.length !== 3) return String(value);
        const time = parts[1] ? parts[1].slice(0, 5) : "";
        return date[2] + "/" + date[1] + "/" + date[0] + (time ? " " + time : "");
    }

    function formatDuration(value) {
        const seconds = Math.max(0, Number(value) || 0);
        if (!seconds) return "—";
        const totalMinutes = Math.floor(seconds / 60);
        const days = Math.floor(totalMinutes / 1440);
        const hours = Math.floor((totalMinutes % 1440) / 60);
        const minutes = totalMinutes % 60;
        const parts = [];
        if (days) parts.push(days + (days === 1 ? " dia" : " dias"));
        if (hours) parts.push(hours + (hours === 1 ? " hora" : " horas"));
        if (minutes) parts.push(minutes + (minutes === 1 ? " minuto" : " minutos"));
        return parts.join(" e ") || "Menos de 1 minuto";
    }

    function addTextCell(row, value, secondary) {
        const cell = document.createElement("td");
        const main = document.createElement("span");
        main.className = "history-cell-main";
        main.textContent = value || "—";
        cell.appendChild(main);
        if (secondary) {
            const sub = document.createElement("span");
            sub.className = "history-cell-sub";
            sub.textContent = secondary;
            cell.appendChild(sub);
        }
        row.appendChild(cell);
    }

    function renderRows(movements) {
        rowsContainer.replaceChildren();
        if (!movements.length) {
            const row = document.createElement("tr");
            const cell = document.createElement("td");
            cell.className = "history-empty";
            cell.colSpan = 9;
            cell.textContent = "Nenhuma movimentação corresponde aos filtros aplicados.";
            row.appendChild(cell);
            rowsContainer.appendChild(row);
            return;
        }

        movements.forEach(function (movement) {
            const row = document.createElement("tr");
            addTextCell(row, movement.roomName, [movement.roomNumber, movement.roomBlock].filter(Boolean).join(" · "));
            const keyCell = document.createElement("td");
            keyCell.appendChild(window.AppKeyDisplay.create(Object.assign({}, movement, {
                keyName: movement.keyName || "Chave"
            })));
            row.appendChild(keyCell);
            addTextCell(row, movement.userName, window.AppServices.formatRole(movement.userRole));
            addTextCell(row, movement.porterName, window.AppServices.formatRole(movement.porterRole));
            addTextCell(row, formatDateTime(movement.withdrawnAt));
            addTextCell(row, formatDateTime(movement.dueAt));
            addTextCell(row, formatDateTime(movement.returnedAt));

            const statusCell = document.createElement("td");
            const badge = document.createElement("span");
            badge.className = "history-badge " + ({
                Devolvida: "history-badge--returned",
                Pendente: "history-badge--pending",
                Atrasada: "history-badge--late"
            }[movement.status] || "history-badge--pending");
            badge.textContent = movement.status || "Pendente";
            statusCell.appendChild(badge);
            row.appendChild(statusCell);
            addTextCell(row, formatDuration(movement.lateSeconds));
            rowsContainer.appendChild(row);
        });
    }

    function populateSelect(select, items, placeholder, valueFor, labelFor, selectedValue) {
        select.replaceChildren();
        const allOption = document.createElement("option");
        allOption.value = "";
        allOption.textContent = placeholder;
        select.appendChild(allOption);
        items.forEach(function (item) {
            const option = document.createElement("option");
            option.value = valueFor(item);
            option.textContent = labelFor(item);
            select.appendChild(option);
        });
        if (Array.from(select.options).some(function (option) { return option.value === selectedValue; })) {
            select.value = selectedValue;
        }
    }

    function readFilters() {
        return {
            sala: roomSelect.value,
            usuario: userSelect.value,
            data_inicial: startDate.value,
            data_final: endDate.value,
            status: statusSelect.value || "Todos"
        };
    }

    function cleanFilters(filters) {
        const cleaned = {};
        Object.keys(filters).forEach(function (key) {
            if (filters[key] !== "") cleaned[key] = filters[key];
        });
        return cleaned;
    }

    async function loadHistory(filters) {
        rowsContainer.replaceChildren();
        const loadingRow = document.createElement("tr");
        const loadingCell = document.createElement("td");
        loadingCell.className = "history-empty";
        loadingCell.colSpan = 9;
        loadingCell.textContent = "Carregando movimentações...";
        loadingRow.appendChild(loadingCell);
        rowsContainer.appendChild(loadingRow);
        countLabel.textContent = "Carregando movimentações...";

        try {
            const response = await window.AppServices.reports.history(cleanFilters(filters));
            if (!response || !Array.isArray(response.data) || !response.options ||
                !Array.isArray(response.options.salas) || !Array.isArray(response.options.usuarios)) {
                throw new Error("O servidor retornou dados inválidos para o histórico.");
            }

            populateSelect(
                roomSelect,
                response.options.salas,
                "Todas as salas",
                function (room) { return String(room.id); },
                function (room) {
                    return room.name + (room.numero ? " · " + room.numero : "") +
                        (room.bloco ? " · Bloco " + room.bloco : "");
                },
                filters.sala
            );
            populateSelect(
                userSelect,
                response.options.usuarios,
                "Todas as pessoas",
                function (user) { return user.filterId; },
                function (user) {
                    return user.name + (user.role ? " — " + window.AppServices.formatRole(user.role) : "");
                },
                filters.usuario
            );

            appliedFilters = Object.assign({}, filters);
            renderRows(response.data);
            countLabel.textContent = response.data.length +
                (response.data.length === 1 ? " movimentação encontrada" : " movimentações encontradas");
            window.AppFeedback.clear(feedback);
        } catch (error) {
            countLabel.textContent = "Não foi possível carregar o histórico.";
            const row = document.createElement("tr");
            const cell = document.createElement("td");
            cell.className = "history-empty";
            cell.colSpan = 9;
            cell.textContent = "Não foi possível carregar as movimentações.";
            row.appendChild(cell);
            rowsContainer.replaceChildren(row);
            window.AppFeedback.show(
                feedback,
                error.message || "Não foi possível carregar o histórico.",
                "error"
            );
        }
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? "" : value).replace(/[&<>"']/g, function (character) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                "\"": "&quot;",
                "'": "&#39;"
            }[character];
        });
    }

    function selectedLabel(select, value, fallback) {
        const option = Array.from(select.options).find(function (item) {
            return item.value === value;
        });
        return option ? option.textContent : fallback;
    }

    function reportRow(movement) {
        const person = [
            movement.userName || "Responsável não informado",
            window.AppServices.formatRole(movement.userRole)
        ]
            .filter(Boolean).join(" — ");
        const porter = [
            movement.porterName || "Não informado",
            window.AppServices.formatRole(movement.porterRole)
        ]
            .filter(Boolean).join(" — ");
        const room = [movement.roomName, movement.roomNumber, movement.roomBlock]
            .filter(Boolean).join(" · ");
        const key = [
            movement.keyName || "Chave sem nome",
            movement.keyNumber ? "Nº " + movement.keyNumber : "",
            "ID " + movement.keyId
        ].filter(Boolean).join(" · ");
        return "<tr>" +
            "<td>" + escapeHtml(room) + "</td>" +
            "<td>" + escapeHtml(key) + "</td>" +
            "<td>" + escapeHtml(person) + "</td>" +
            "<td>" + escapeHtml(porter) + " (ID " + escapeHtml(movement.porterId) + ")</td>" +
            "<td>" + escapeHtml(formatDateTime(movement.withdrawnAt)) + "</td>" +
            "<td>" + escapeHtml(formatDateTime(movement.dueAt)) + "</td>" +
            "<td>" + escapeHtml(formatDateTime(movement.returnedAt)) + "</td>" +
            "<td>" + escapeHtml(movement.status || "Pendente") + "</td>" +
            "<td>" + escapeHtml(formatDuration(movement.lateSeconds)) + "</td>" +
            "<td>" + escapeHtml(movement.notes || "—") + "</td>" +
            "</tr>";
    }

    function buildReport(movements, filters) {
        const logoUrl = new URL("../assets/icons/Logo%20sistema%20de%20chaves.svg", window.location.href).href;
        const period = [
            filters.data_inicial ? formatDateTime(filters.data_inicial) : "Início não definido",
            filters.data_final ? formatDateTime(filters.data_final) : "Fim não definido"
        ].join(" até ");
        const room = filters.sala
            ? selectedLabel(roomSelect, filters.sala, "Sala selecionada")
            : "Todas as salas";
        const user = filters.usuario
            ? selectedLabel(userSelect, filters.usuario, "Pessoa selecionada")
            : "Todas as pessoas";
        const status = filters.status || "Todos";
        const rows = movements.length
            ? movements.map(reportRow).join("")
            : '<tr><td class="empty" colspan="10">Nenhuma movimentação corresponde aos filtros.</td></tr>';

        return "<!doctype html><html lang=\"pt-BR\"><head><meta charset=\"utf-8\">" +
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">" +
            "<title>Relatório de movimentações</title><style>" +
            "@page{size:landscape;margin:12mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#17202b;margin:0}" +
            ".report{max-width:1500px;margin:24px auto;padding:0 20px}.heading{display:flex;align-items:center;gap:14px;border-bottom:2px solid #07325a;padding-bottom:14px}" +
            ".heading img{width:54px;height:54px;object-fit:contain}.heading h1{margin:0;color:#07325a;font-size:23px}.heading p{margin:5px 0 0;color:#667085}" +
            ".meta{display:grid;grid-template-columns:repeat(4,1fr);gap:8px 18px;margin:18px 0;padding:13px;border:1px solid #d5dbe3;border-radius:6px;font-size:12px}" +
            ".meta strong{display:block;margin-bottom:3px;color:#475467}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:10px}" +
            "th,td{padding:8px 6px;border:1px solid #d5dbe3;text-align:left;vertical-align:top;overflow-wrap:anywhere}th{background:#e8edf2;color:#25364a}" +
            ".empty{text-align:center;color:#667085;padding:20px}.footer{margin-top:12px;color:#667085;font-size:10px}.print{margin:0 0 14px;padding:10px 18px;border:0;border-radius:6px;background:#063b73;color:#fff;font-weight:bold;cursor:pointer}" +
            "@media print{.report{max-width:none;margin:0;padding:0}.print{display:none}thead{display:table-header-group}tr{break-inside:avoid}}" +
            "</style></head><body><main class=\"report\"><button class=\"print\" id=\"print-report\" type=\"button\">Imprimir / salvar em PDF</button>" +
            "<header class=\"heading\"><img src=\"" + escapeHtml(logoUrl) + "\" alt=\"\">" +
            "<div><h1>Sistema de Gerenciamento de Chaves</h1><p>Histórico e auditoria de movimentações</p></div></header>" +
            "<section class=\"meta\">" +
            "<div><strong>Período da retirada</strong>" + escapeHtml(period) + "</div>" +
            "<div><strong>Sala</strong>" + escapeHtml(room) + "</div>" +
            "<div><strong>Pessoa</strong>" + escapeHtml(user) + "</div>" +
            "<div><strong>Status</strong>" + escapeHtml(status) + "</div>" +
            "<div><strong>Emitido em</strong>" + escapeHtml(new Date().toLocaleString("pt-BR")) + "</div>" +
            "<div><strong>Registros</strong>" + movements.length + "</div></section>" +
            "<div class=\"table-wrap\"><table><thead><tr><th>Sala</th><th>Chave</th><th>Pessoa que retirou</th>" +
            "<th>Registrada por</th><th>Retirada</th><th>Prevista</th><th>Devolução efetiva</th><th>Situação</th>" +
            "<th>Tempo de atraso</th><th>Observações</th></tr></thead><tbody>" + rows +
            "</tbody></table></div><p class=\"footer\">Relatório gerado com os filtros aplicados na tela de Histórico / Auditoria.</p>" +
            "</main><script>document.getElementById('print-report').addEventListener('click',function(){window.print()})</script></body></html>";
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        loadHistory(readFilters());
    });

    document.getElementById("history-clear").addEventListener("click", function () {
        form.reset();
        statusSelect.value = "Todos";
        loadHistory(readFilters());
    });

    exportButton.addEventListener("click", async function () {
        const reportWindow = window.open("", "_blank");
        if (!reportWindow) {
            window.AppFeedback.show(feedback, "Permita a abertura de janelas para gerar o relatório.", "error");
            return;
        }

        exportButton.disabled = true;
        try {
            const response = await window.AppServices.reports.history(cleanFilters(appliedFilters));
            if (!response || !Array.isArray(response.data)) {
                throw new Error("O servidor retornou dados inválidos para o relatório.");
            }
            reportWindow.document.open();
            reportWindow.document.write(buildReport(response.data, appliedFilters));
            reportWindow.document.close();
            reportWindow.focus();
            window.AppFeedback.show(
                feedback,
                "Relatório gerado. Use Imprimir / salvar em PDF na nova janela.",
                "success"
            );
        } catch (error) {
            reportWindow.close();
            window.AppFeedback.show(
                feedback,
                error.message || "Não foi possível gerar o relatório.",
                "error"
            );
        } finally {
            exportButton.disabled = false;
        }
    });

    loadHistory(readFilters());
});
}());
