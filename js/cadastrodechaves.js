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

    if (editingId && window.AppProfile.get().role !== "GERENTE") {
        window.location.replace("listadechaves.php");
        return;
    }

    async function initialize() {
        if (!editingId) return;
        title.textContent = "Editar chave";
        try {
            const key = await window.AppServices.keys.get(editingId);
            nameField.value = key.name;
            numberField.value = key.number;
            blockField.value = key.block || "";
        } catch (error) {
            window.AppFeedback.show(feedback, error.message || "Não foi possível carregar a chave.", "error");
            form.querySelector('[type="submit"]').disabled = true;
        }
    }

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
            name: nameField.value.trim(),
            number: numberField.value.trim(),
            block: blockField.value.trim()
        };
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
