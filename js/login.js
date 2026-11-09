(function () {
    const form = document.getElementById("login-form");
    const feedback = document.getElementById("login-feedback");
    const submitButton = form.querySelector('[type="submit"]');

    window.AppProfile.ready.then(function (user) {
        if (user) {
            window.location.replace("dashboard.php");
        }
    }).catch(function (error) {
        window.AppFeedback.show(
            feedback,
            error.message || "Não foi possível verificar a sessão com o servidor.",
            "error"
        );
    });

    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        const identifier = document.getElementById("identificador").value.trim();
        const password = document.getElementById("senha").value;
        if (!form.checkValidity()) {
            form.reportValidity();
            window.AppFeedback.show(feedback, "Informe seu CPF ou e-mail e sua senha.", "error");
            return;
        }

        submitButton.disabled = true;
        try {
            const response = await window.AppServices.auth.login(identifier, password);
            if (!response.user || !["GERENTE", "PORTEIRO"].includes(String(response.user.role).toUpperCase())) {
                throw new Error("O servidor não retornou um perfil GERENTE ou PORTEIRO válido.");
            }
            window.location.replace("dashboard.php");
        } catch (error) {
            const message = error instanceof TypeError
                ? "Não foi possível conectar ao servidor de autenticação. Verifique se o projeto está sendo servido pelo PHP."
                : error.message || "Não foi possível realizar o login.";
            window.AppFeedback.show(feedback, message, "error");
            submitButton.disabled = false;
        }
    });
}());
