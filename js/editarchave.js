(function () {
    window.AppAccess.run(function () {
        const form = document.getElementById("key-edit-form");
        const feedback = document.getElementById("key-edit-feedback");
        const summary = document.getElementById("key-edit-summary");
        const nameField = document.getElementById("key-edit-name");
        const roomSelect = document.getElementById("key-edit-room");
        const numberField = document.getElementById("key-edit-number");
        const blockField = document.getElementById("key-edit-block");
        const saveButton = form.querySelector('[type="submit"]');
        const keyId = new URLSearchParams(window.location.search).get("id");
        let rooms = [];

        function updateBlock() {
            const room = rooms.find(function (item) {
                return String(item.id) === roomSelect.value;
            });
            blockField.value = room ? room.bloco || "" : "";
        }

        async function initialize() {
            if (!keyId || !/^\d+$/.test(keyId)) {
                window.AppFeedback.show(feedback, "Identificador de chave inválido.", "error");
                return;
            }

            try {
                const results = await Promise.all([
                    window.AppServices.rooms.list(),
                    window.AppServices.keys.get(keyId)
                ]);
                rooms = results[0];
                const key = results[1];
                roomSelect.replaceChildren();
                rooms.forEach(function (room) {
                    const option = document.createElement("option");
                    option.value = String(room.id);
                    option.textContent = room.name + " — Nº " + room.numero + " — Bloco " + room.bloco;
                    roomSelect.appendChild(option);
                });

                nameField.value = key.name || "";
                numberField.value = key.number || "";
                roomSelect.value = String(key.salaId);
                updateBlock();
                const title = document.createElement("strong");
                title.textContent = key.name || "Nome não informado";
                const details = document.createElement("span");
                details.textContent = "Nº " + key.number + " · " +
                    (key.roomName || "Sala não informada") + " · Status: " + key.status;
                summary.replaceChildren(title, details);
                summary.hidden = false;
                saveButton.disabled = false;
            } catch (error) {
                window.AppFeedback.show(
                    feedback,
                    error.message || "Não foi possível carregar os dados da chave.",
                    "error"
                );
            }
        }

        roomSelect.addEventListener("change", updateBlock);
        form.querySelector("[data-cancel]").addEventListener("click", function () {
            window.location.href = "listadechaves.php";
        });

        form.addEventListener("submit", async function (event) {
            event.preventDefault();
            nameField.value = nameField.value.trim();
            numberField.value = numberField.value.trim();
            if (!form.checkValidity()) {
                form.reportValidity();
                window.AppFeedback.show(feedback, "Informe nome, sala e número válidos para a chave.", "error");
                return;
            }

            saveButton.disabled = true;
            try {
                await window.AppServices.keys.update(keyId, {
                    name: nameField.value,
                    roomId: roomSelect.value,
                    number: numberField.value
                });
                window.AppFeedback.show(feedback, "Chave atualizada com sucesso.", "success");
                window.setTimeout(function () { window.location.href = "listadechaves.php"; }, 800);
            } catch (error) {
                window.AppFeedback.show(feedback, error.message || "Não foi possível atualizar a chave.", "error");
                saveButton.disabled = false;
            }
        });

        initialize();
    });
}());
