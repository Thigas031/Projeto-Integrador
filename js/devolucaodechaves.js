(function () {
    const select = document.getElementById("return-key");
    const feedback = document.getElementById("return-feedback");
    const form = document.getElementById("return-form");
    const fields = {
        user: document.getElementById("return-user"),
        date: document.getElementById("return-date"),
        time: document.getElementById("return-time")
    };
    let requestedKey = new URLSearchParams(window.location.search).get("chave");

    function getOpenKeys() {
        return DemoKeys.read().keys.filter(function (key) {
            return key.status !== "disponivel";
        });
    }

    function fillMovementDetails() {
        const key = getOpenKeys().find(function (item) {
            return item.id === select.value;
        });

        fields.user.value = key ? key.user : "";
        fields.date.value = key ? key.date : "";
        fields.time.value = key ? key.time : "";
    }

    function renderOptions() {
        const keys = getOpenKeys();

        select.innerHTML = '<option value="">Selecione uma chave</option>' + keys.map(function (key) {
            return `<option value="${key.id}">${key.room} — ${key.id}</option>`;
        }).join("");

        if (requestedKey && keys.some(function (key) {
            return key.id === requestedKey;
        })) {
            select.value = requestedKey;
            requestedKey = null;
        }

        fillMovementDetails();
    }

    function cancelReturn() {
        window.location.href = "listadechaves.html";
    }

    function confirmReturn(event) {
        event.preventDefault();

        if (!select.value) {
            window.AppFeedback.show(feedback, "Selecione uma chave para confirmar a devolução.", "error");
            return;
        }

        DemoKeys.returnKey(select.value);
        window.AppFeedback.show(feedback, "Devolução registrada nos dados de demonstração.", "success");
        renderOptions();
    }

    select.addEventListener("change", fillMovementDetails);
    document.querySelector("[data-cancel]").addEventListener("click", cancelReturn);
    form.addEventListener("submit", confirmReturn);
    renderOptions();
}());
