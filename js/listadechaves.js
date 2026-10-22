(function () {
    const search = document.getElementById("busca");
    const rows = Array.from(document.querySelectorAll("#corpo-tabela tr"));
    const returnButton = document.getElementById("devolver-chave");
    const newKeyButton = document.getElementById("nova-chave");

    function filterKeys() {
        const term = search.value.toLowerCase();

        rows.forEach(function (row) {
            row.hidden = !row.textContent.toLowerCase().includes(term);
        });
    }

    function openReturnPage() {
        window.location.href = "devolucaodechaves.html";
    }

    function openNewKeyPage() {
        window.location.href = "cadastrodechaves.html";
    }

    if (search) {
        search.addEventListener("input", filterKeys);
    }

    if (returnButton) {
        returnButton.addEventListener("click", openReturnPage);
    }

    if (newKeyButton) {
        newKeyButton.addEventListener("click", openNewKeyPage);
    }
}());
