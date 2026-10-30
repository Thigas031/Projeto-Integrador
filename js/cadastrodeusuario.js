(function () {
    const form = document.getElementById("user-form");
    const feedback = document.getElementById("user-feedback");
    const cancelButton = document.querySelector("[data-cancel]");

    function cancelRegistration() {
        window.location.href = "dashboard.html";
    }

    function submitRegistration(event) {
        event.preventDefault();

        const phone = document.getElementById("user-phone").value.trim();
        const phoneDigits = phone.replace(/\D/g, "");

        if (!form.checkValidity()) {
            form.reportValidity();
            window.AppFeedback.show(feedback, "Preencha os campos corretamente.", "error");
            return;
        }

        if (!/^[0-9()+. \-]+$/.test(phone) || phoneDigits.length < 10 || phoneDigits.length > 11) {
            window.AppFeedback.show(feedback, "Informe um telefone com DDD e número válido.", "error");
            return;
        }

        window.AppFeedback.show(
            feedback,
            "Usuário cadastrado para demonstração. A gravação no banco será integrada posteriormente.",
            "success"
        );
        form.reset();
    }

    cancelButton.addEventListener("click", cancelRegistration);
    form.addEventListener("submit", submitRegistration);
}());
