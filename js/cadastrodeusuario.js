(function () {
    const form = document.getElementById("user-form");
    const feedback = document.getElementById("user-feedback");
    const cancelButton = document.querySelector("[data-cancel]");

    function showFeedback(text, type) {
        feedback.textContent = text;
        feedback.className = `form-feedback show ${type}`;
    }

    function cancelRegistration() {
        window.location.href = "dashboard.html";
    }

    function submitRegistration(event) {
        event.preventDefault();

        const phone = document.getElementById("user-phone").value.trim();
        const phoneDigits = phone.replace(/\D/g, "");

        if (!form.checkValidity()) {
            form.reportValidity();
            showFeedback("Preencha os campos corretamente.", "error");
            return;
        }

        if (!/^[0-9()+. \-]+$/.test(phone) || phoneDigits.length < 10 || phoneDigits.length > 11) {
            showFeedback("Informe um telefone com DDD e número válido.", "error");
            return;
        }

        showFeedback(
            "Usuário cadastrado para demonstração. A gravação no banco será integrada posteriormente.",
            "success"
        );
        form.reset();
    }

    cancelButton.addEventListener("click", cancelRegistration);
    form.addEventListener("submit", submitRegistration);
}());
