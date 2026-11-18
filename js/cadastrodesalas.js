(function () {
    window.AppAccess.run(function () {
        const form = document.getElementById("room-form");
        const feedback = document.getElementById("room-feedback");
        const nameField = document.getElementById("room-name");
        const numberField = document.getElementById("room-number");
        const blockField = document.getElementById("room-block");
        const cancelButton = document.getElementById("cancel-room");
        const saveButton = form.querySelector('[type="submit"]');
        const title = document.getElementById("room-form-title");
        const editContext = document.getElementById("room-edit-context");
        const editingId = new URLSearchParams(window.location.search).get("id");

        function roomLabel(room) {
            return room.name + " — Nº " + room.numero + " — Bloco " + room.bloco;
        }

        cancelButton.addEventListener("click", function () {
            window.location.href = "listadesalas.php";
        });

        async function initialize() {
            if (!editingId) {
                saveButton.disabled = false;
                return;
            }

            title.textContent = "Editar sala";
            try {
                const rooms = await window.AppServices.rooms.list();
                const room = rooms.find(function (item) {
                    return String(item.id) === String(editingId);
                });
                if (!room) {
                    throw new Error("A sala solicitada não foi encontrada.");
                }

                nameField.value = room.name;
                numberField.value = room.numero;
                blockField.value = room.bloco;
                editContext.textContent = "Editando: " + roomLabel(room);
                editContext.hidden = false;
                saveButton.disabled = false;
            } catch (error) {
                window.AppFeedback.show(
                    feedback,
                    error.message || "Não foi possível carregar os dados da sala.",
                    "error"
                );
            }
        }

        form.addEventListener("submit", async function (event) {
            event.preventDefault();

            nameField.value = nameField.value.trim();
            numberField.value = numberField.value.trim();
            blockField.value = blockField.value.trim();

            if (!form.checkValidity()) {
                form.reportValidity();
                window.AppFeedback.show(
                    feedback,
                    "Informe o nome, o número e o bloco da sala.",
                    "error"
                );
                return;
            }

            saveButton.disabled = true;
            try {
                const payload = window.AppServices.rooms.prepareWrite({
                    name: nameField.value,
                    number: numberField.value,
                    block: blockField.value
                });
                if (editingId) {
                    await window.AppServices.rooms.update(editingId, payload);
                    window.location.replace("listadesalas.php?result=updated");
                    return;
                }

                await window.AppServices.rooms.create(payload);
                window.location.replace("listadesalas.php?result=created");
            } catch (error) {
                window.AppFeedback.show(
                    feedback,
                    error.message || "Não foi possível salvar a sala.",
                    "error"
                );
                saveButton.disabled = false;
            }
        });

        initialize();
    });
}());
