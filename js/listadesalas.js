(function () {
    window.AppAccess.run(function () {
        const searchField = document.getElementById("room-search");
        const tableBody = document.getElementById("rooms-table-body");
        const feedback = document.getElementById("rooms-feedback");
        const count = document.getElementById("rooms-count");
        const resultMessages = {
            created: "Sala cadastrada com sucesso.",
            updated: "Sala atualizada com sucesso."
        };
        const result = new URLSearchParams(window.location.search).get("result");
        let currentRooms = [];
        let loadFailed = false;

        function setFeedback(message, type) {
            if (!message) {
                window.AppFeedback.clear(feedback);
                return;
            }
            window.AppFeedback.show(feedback, message, type);
        }

        function render() {
            const query = searchField.value.trim().toLocaleLowerCase();
            const filteredRooms = currentRooms.filter(function (room) {
                return [room.name, room.numero, room.bloco].some(function (value) {
                    return String(value || "").toLocaleLowerCase().includes(query);
                });
            });

            tableBody.replaceChildren();
            if (!filteredRooms.length) {
                const row = document.createElement("tr");
                const cell = document.createElement("td");
                cell.colSpan = 4;
                cell.textContent = loadFailed
                    ? "Não foi possível carregar as salas."
                    : currentRooms.length
                        ? "Nenhuma sala corresponde à pesquisa."
                        : "Nenhuma sala cadastrada. Use “Nova sala” para adicionar uma.";
                row.appendChild(cell);
                tableBody.appendChild(row);
                count.textContent = "0 salas";
                return;
            }

            filteredRooms.forEach(function (room) {
                const row = document.createElement("tr");
                const name = document.createElement("td");
                const number = document.createElement("td");
                const block = document.createElement("td");
                const actions = document.createElement("td");
                const edit = document.createElement("a");
                const remove = document.createElement("button");

                name.textContent = room.name;
                number.textContent = room.numero;
                block.textContent = room.bloco;

                edit.className = "room-action";
                edit.href = "cadastrodesalas.php?id=" + encodeURIComponent(String(room.id));
                edit.textContent = "Editar";

                remove.className = "room-action room-action--danger";
                remove.type = "button";
                remove.dataset.deleteId = String(room.id);
                remove.textContent = "Excluir";

                actions.className = "room-actions";
                actions.append(edit, remove);
                row.append(name, number, block, actions);
                tableBody.appendChild(row);
            });

            count.textContent = filteredRooms.length + (filteredRooms.length === 1 ? " sala" : " salas");
        }

        async function loadRooms() {
            try {
                currentRooms = await window.AppServices.rooms.list();
                if (!Array.isArray(currentRooms)) {
                    throw new Error("O servidor retornou uma lista de salas inválida.");
                }
                loadFailed = false;
                render();
            } catch (error) {
                currentRooms = [];
                loadFailed = true;
                render();
                setFeedback(error.message || "Não foi possível carregar as salas.", "error");
            }
        }

        searchField.addEventListener("input", render);

        tableBody.addEventListener("click", async function (event) {
            const button = event.target.closest("[data-delete-id]");
            if (!button) return;

            const room = currentRooms.find(function (item) {
                return String(item.id) === button.dataset.deleteId;
            });
            if (!room) return;

            const label = room.name + " — Nº " + room.numero + " — Bloco " + room.bloco;
            if (!window.confirm("Deseja excluir " + label + "?")) return;

            button.disabled = true;
            try {
                await window.AppServices.rooms.delete(room.id);
                setFeedback("Sala excluída com sucesso.", "success");
                await loadRooms();
            } catch (error) {
                setFeedback(
                    error.message || "Não foi possível excluir a sala. Verifique se há chaves vinculadas.",
                    "error"
                );
                button.disabled = false;
            }
        });

        if (Object.prototype.hasOwnProperty.call(resultMessages, result)) {
            setFeedback(resultMessages[result], "success");
        }
        loadRooms();
    });
}());
