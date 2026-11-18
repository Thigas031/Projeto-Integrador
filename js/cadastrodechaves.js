(function () {
window.AppAccess.run(function () {
    const form = document.getElementById("key-form");
    const feedback = document.getElementById("key-feedback");
    const nameField = document.getElementById("nome-sala");
    const numberField = document.getElementById("numero-chave");
    const blockField = document.getElementById("bloco");
    const title = document.querySelector(".cadastro-chaves-header h1");
    const formData = new URLSearchParams(window.location.search);
    const editingId = formData.get("id");
    const cancelButton = document.querySelector("[data-cancel]");
    const roomsList = document.createElement("datalist");
    let rooms = [];

    roomsList.id = "salas-disponiveis";
    nameField.setAttribute("list", roomsList.id);
    nameField.maxLength = 180;
    nameField.parentElement.appendChild(roomsList);

    function roomLabel(room) {
        return room.name + " — Nº " + (room.numero || "sem número") + " — Bloco " + (room.bloco || "sem bloco");
    }

    function selectedRoom() {
        const valor = nameField.value.trim().toLocaleLowerCase();
        const salasComMesmoNome = rooms.filter(function (room) {
            return String(room.name).trim().toLocaleLowerCase() === valor;
        });
        return rooms.find(function (room) { return roomLabel(room) === nameField.value; }) ||
            (salasComMesmoNome.length === 1 ? salasComMesmoNome[0] : null);
    }

    function updateRoomBlock() {
        const room = selectedRoom();
        blockField.value = room ? room.bloco || "" : "";
        blockField.readOnly = Boolean(room);
    }

    if (editingId && !window.AppProfile.isAllowed("GERENTE")) {
        window.location.replace("listadechaves.php");
        return;
    }

    async function initialize() {
        try {
            rooms = await window.AppServices.rooms.list();
            roomsList.replaceChildren();
            rooms.forEach(function (room) {
                const option = document.createElement("option");
                option.value = roomLabel(room);
                roomsList.appendChild(option);
            });
            if (!rooms.length) {
                form.querySelector('[type="submit"]').disabled = true;
                window.AppFeedback.show(feedback, "Cadastre uma sala antes de cadastrar chaves.", "error");
                return;
            }

            if (!editingId) return;
            title.textContent = "Editar chave";
            const key = await window.AppServices.keys.get(editingId);
            const room = rooms.find(function (item) { return String(item.id) === String(key.salaId); });
            nameField.value = room ? roomLabel(room) : "";
            numberField.value = key.number;
            updateRoomBlock();
        } catch (error) {
            window.AppFeedback.show(feedback, error.message || "Não foi possível carregar salas ou chave.", "error");
            form.querySelector('[type="submit"]').disabled = true;
        }
    }

    nameField.addEventListener("input", updateRoomBlock);

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

        const values = {
            roomId: selectedRoom() ? selectedRoom().id : "",
            number: numberField.value.trim(),
        };
        if (!values.roomId) {
            window.AppFeedback.show(feedback, "Selecione uma sala cadastrada na lista.", "error");
            nameField.focus();
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
