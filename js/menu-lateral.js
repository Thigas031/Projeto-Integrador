(function () {
    async function carregarMenu() {
        const container = document.getElementById("menu-lateral-container");

        if (!container) {
            return;
        }

        if (!container.querySelector(".menu-lateral")) {
            try {
                const resposta = await fetch(container.dataset.menuUrl);

                if (!resposta.ok) {
                    throw new Error(`HTTP ${resposta.status}`);
                }

                const html = await resposta.text();

                if (!container.querySelector(".menu-lateral")) {
                    container.insertAdjacentHTML("afterbegin", html);
                }
            } catch (erro) {
                console.error("Não foi possível carregar o menu lateral.", erro);
                return;
            }
        }

        const menu = container.querySelector(".menu-lateral");

        if (!menu) {
            console.error("O arquivo carregado não contém o menu lateral.");
            return;
        }

        const links = menu.querySelectorAll("nav a");
        const paginaAtual = container.dataset.page;
        menu.dataset.page = paginaAtual;

        const linksDaPagina = Array.from(links).filter(function (link) {
            return link.dataset.page === paginaAtual ||
                link.dataset.pageAlias === paginaAtual;
        });

        if (linksDaPagina.length !== 1) {
            return;
        }

        links.forEach(function (link) {
            link.classList.remove("active");
            link.removeAttribute("aria-current");
        });

        linksDaPagina[0].classList.add("active");
        linksDaPagina[0].setAttribute("aria-current", "page");
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", carregarMenu, { once: true });
    } else {
        carregarMenu();
    }
})();
