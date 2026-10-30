(function () {
    const form = document.getElementById("withdrawal-form");
    const feedback = document.getElementById("withdrawal-feedback");
    const keySelect = document.getElementById("chave");
    const requestedKey = new URLSearchParams(window.location.search).get("chave");

    if (requestedKey && Array.from(keySelect.options).some(function (option) {
        return option.value === requestedKey;
    })) {
        keySelect.value = requestedKey;
    }

    function cancelWithdrawal() {
        window.location.href = "listadechaves.html";
    }

    function confirmWithdrawal(event) {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            window.AppFeedback.show(feedback, "Preencha os campos obrigatórios da retirada.", "error");
            return;
        }

        window.AppFeedback.show(feedback, "Retirada validada para demonstração; nenhum dado foi salvo.", "success");
    }

    form.querySelector("[data-cancel]").addEventListener("click", cancelWithdrawal);
    form.addEventListener("submit", confirmWithdrawal);
}());