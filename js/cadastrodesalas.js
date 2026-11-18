(function () {
    window.AppAccess.run(function () {
        const form = document.getElementById("room-form");
        const feedback = document.getElementById("room-feedback");
        const nameField = document.getElementById("room-name");
        const numberField = document.getElementById("room-number");
        const descriptionField = document.getElementById("room-description");
        const cancelButton = document.getElementById("cancel-room");

        function returnToPreviousPage() {
            const referrer = document.referrer;
            if (referrer) {
                const previousPage = new URL(referrer, window.location.href);
                if (previousPage.origin === window.location.origin &&
                    previousPage.pathname !== window.location.pathname &&
                    !previousPage.pathname.endsWith("/login.html")) {
                    window.location.replace(previousPage.href);
                    return;
                }
            }
            window.location.replace("dashboard.php");
        }

        cancelButton.addEventListener("click", function () {
            form.reset();
            window.AppFeedback.clear(feedback);
            returnToPreviousPage();
        });

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            nameField.value = nameField.value.trim();
            numberField.value = numberField.value.trim();
            descriptionField.value = descriptionField.value.trim();

            if (!nameField.value || !numberField.value) {
                const missingField = !nameField.value ? nameField : numberField;
                window.AppFeedback.show(
                    feedback,
                    "Informe o nome e o número da sala.",
                    "error"
                );
                missingField.focus();
                return;
            }

            if (!form.checkValidity()) {
                form.reportValidity();
                window.AppFeedback.show(
                    feedback,
                    "Confira os limites de caracteres dos campos.",
                    "error"
                );
                return;
            }

            try {
                const payload = window.AppServices.rooms.prepareCreate({
                    name: nameField.value,
                    number: numberField.value,
                    description: descriptionField.value
                });
                nameField.value = payload.nome_sala;
                numberField.value = payload.numero_sala;
                descriptionField.value = payload.descricao;
                window.AppFeedback.show(
                    feedback,
                    "Dados validados e preparados. O envio e a gravação ainda dependem da integração com o backend.",
                    "success"
                );
            } catch (error) {
                window.AppFeedback.show(
                    feedback,
                    error.message || "Não foi possível preparar o cadastro da sala.",
                    "error"
                );
            }
        });
    });
}());
