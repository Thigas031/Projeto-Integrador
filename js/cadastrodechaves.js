(function () {
window.AppAccess.run(function () {
    const form = document.getElementById("key-form");
    const feedback = document.getElementById("key-feedback");
    const roomSelect = document.getElementById("nome-sala");
    const numberField = document.getElementById("numero-chave");
    const blockField = document.getElementById("bloco");
    const title = document.querySelector(".cadastro-chaves-header h1");
    const formData = new URLSearchParams(window.location.search);
    const editingId = formData.get("id");
    const cancelButton = document.querySelector("[data-cancel]");
    const saveButton = form.querySelector('[type="submit"]');
    const manageRoomsLink = document.getElementById("manage-rooms-link");
    let rooms = [];

    saveButton.disabled = true;

    function roomLabel(room) {
        return room.name + " — Nº " + (room.numero || "sem número") + " — Bloco " + (room.bloco || "sem bloco");
    }

    function selectedRoom() {
        return rooms.find(function (room) {
            return String(room.id) === roomSelect.value;
        }) || null;
    }

    function updateRoomBlock() {
        const room = selectedRoom();
        blockField.value = room ? room.bloco || "" : "";
    }

    if (editingId && !window.AppProfile.isAllowed("GERENTE")) {
        window.location.replace("listadechaves.php");
        return;
    }

    async function initialize() {
        try {
            rooms = await window.AppServices.rooms.list();
            roomSelect.replaceChildren();
            const placeholder = document.createElement("option");
            placeholder.value = "";
            placeholder.textContent = rooms.length ? "Selecione uma sala cadastrada" : "Nenhuma sala cadastrada";
            roomSelect.appendChild(placeholder);

            rooms.forEach(function (room) {
                const option = document.createElement("option");
                option.value = String(room.id);
                option.textContent = roomLabel(room);
                roomSelect.appendChild(option);
            });

            if (!rooms.length) {
                roomSelect.disabled = true;
                manageRoomsLink.hidden = false;
                window.AppFeedback.show(feedback, "Cadastre uma sala na administração antes de cadastrar chaves.", "error");
                return;
            }

            roomSelect.disabled = false;
            if (editingId) {
                title.textContent = "Editar chave";
                const key = await window.AppServices.keys.get(editingId);
                const room = rooms.find(function (item) { return String(item.id) === String(key.salaId); });
                if (!room) {
                    throw new Error("A sala vinculada a esta chave não está disponível.");
                }
                roomSelect.value = String(room.id);
                numberField.value = key.number;
                updateRoomBlock();
            }
            saveButton.disabled = false;
        } catch (error) {
            window.AppFeedback.show(feedback, error.message || "Não foi possível carregar salas ou chave.", "error");
            roomSelect.disabled = true;
            saveButton.disabled = true;
        }
    }

    roomSelect.addEventListener("change", updateRoomBlock);

    cancelButton.addEventListener("click", function () {
        window.location.href = "listadechaves.php";
    });

    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (!form.checkValidity()) {
            form.reportValidity();
            window.AppFeedback.show(feedback, "Preencha o nome e o número da chave.", "error");
            return;
        }

        const room = selectedRoom();
        const values = {
            roomId: room ? room.id : "",
            number: numberField.value.trim(),
        };
        if (!values.roomId) {
            window.AppFeedback.show(feedback, "Selecione uma sala cadastrada na lista.", "error");
            roomSelect.focus();
            return;
        }
        try {
            if (editingId) {
                await window.AppServices.keys.update(editingId, values);
                window.AppFeedback.show(feedback, "Chave atualizada com sucesso.", "success");
            } else {
                await window.AppServices.keys.create(values);
                window.AppFeedback.show(feedback, "Chave cadastrada com sucesso.", "success");
            }
            form.reset();
            window.setTimeout(function () { window.location.href = "listadechaves.php"; }, 800);
        } catch (error) {
            window.AppFeedback.show(feedback, error.message || "Não foi possível salvar a chave.", "error");
        }
    });

    initialize();
});
}());
