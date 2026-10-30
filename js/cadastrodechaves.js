(function () {
    const form = document.getElementById("key-form");
    const feedback = document.getElementById("key-feedback");
    const cancelButton = document.querySelector("[data-cancel]");

    function cancelRegistration() {
        form.reset();
        window.AppFeedback.clear(feedback);
    }

    function submitRegistration(event) {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            window.AppFeedback.show(feedback, "Preencha o nome da sala e o número da chave.", "error");
            return;
        }

        window.AppFeedback.show(feedback, "Cadastro validado para demonstração; nenhum dado foi salvo.", "success");
        form.reset();
    }

    cancelButton.addEventListener("click", cancelRegistration);
    form.addEventListener("submit", submitRegistration);
}());