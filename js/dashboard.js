(function () {
    const keys = DemoKeys.read().keys;
    const counts = { disponivel: 0, emprestada: 0, atrasada: 0 };

    function updateIndicators() {
        keys.forEach(function (key) {
            counts[key.status] += 1;
        });

        document.getElementById("available").textContent = counts.disponivel;
        document.getElementById("borrowed").textContent = counts.emprestada;
        document.getElementById("late").textContent = counts.atrasada;
        document.getElementById("due").textContent = counts.emprestada + counts.atrasada;
    }

    function renderMovements() {
        const movements = keys.filter(function (key) {
            return key.status !== "disponivel";
        }).slice(0, 3);
        const list = document.getElementById("movements");

        list.innerHTML = movements.map(function (key) {
            return `
                <div class="movement">
                    <span class="movement-avatar" aria-hidden="true">
                        <img src="https://cdn-icons-png.flaticon.com/512/25/25694.png" alt="">
                    </span>
                    <div>
                        <strong>${key.room}</strong>
                        <p>${key.user}</p>
                    </div>
                    <span class="movement-status">Retirada</span>
                    <time>Hoje, ${key.time}</time>
                </div>
            `;
        }).join("") || '<div class="movement">Nenhuma movimentação recente.</div>';
    }

    updateIndicators();
    renderMovements();
}());
