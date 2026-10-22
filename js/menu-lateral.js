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
        const botaoMenu = menu.querySelector(".botao-menu");

        botaoMenu.addEventListener("click", function () {
            const aberto = menu.classList.toggle("is-open");
            botaoMenu.setAttribute("aria-expanded", String(aberto));
            botaoMenu.setAttribute("aria-label", aberto ? "Fechar menu" : "Abrir menu");
        });

        menu.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && menu.classList.contains("is-open")) {
                menu.classList.remove("is-open");
                botaoMenu.setAttribute("aria-expanded", "false");
                botaoMenu.setAttribute("aria-label", "Abrir menu");
                botaoMenu.focus();
            }
        });

        const linksDaPagina = Array.from(links).filter(function (link) {
            return link.dataset.page === paginaAtual ||
                link.dataset.pageAlias === paginaAtual;
        });

        links.forEach(function (link) {
            link.classList.remove("active");
            link.removeAttribute("aria-current");
        });

        if (linksDaPagina.length === 1) {
            linksDaPagina[0].classList.add("active");
            linksDaPagina[0].setAttribute("aria-current", "page");
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", carregarMenu, { once: true });
    } else {
        carregarMenu();
    }
})();
