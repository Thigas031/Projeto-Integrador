(function () {
    function aplicarPerfil(menu, links, container, usuario) {
        const perfil = String(usuario.role || "").trim().toUpperCase();
        if (!["GERENTE", "PORTEIRO"].includes(perfil)) {
            throw new Error("O servidor retornou um cargo não suportado pelo menu.");
        }

        container.dataset.profile = perfil;
        menu.dataset.profile = perfil;
        links.forEach(function (link) {
            const perfisPermitidos = (link.dataset.profiles || "").split(",")
                .map(function (item) { return item.trim().toUpperCase(); });
            link.hidden = !perfisPermitidos.includes(perfil);
        });

        const name = menu.querySelector("[data-user-name]");
        const role = menu.querySelector("[data-user-role]");
        if (!name || !role) {
            throw new Error("O menu lateral não contém a identificação do usuário.");
        }
        name.textContent = usuario.name;
        role.textContent = perfil;
    }

    async function carregarMenu() {
        const container = document.getElementById("menu-lateral-container");
        if (!container) return;

        try {
            const usuario = await window.AppProfile.ready;
            if (!usuario) return;

            if (!container.querySelector(".menu-lateral")) {
                const resposta = await fetch(container.dataset.menuUrl, {
                    credentials: "same-origin"
                });
                if (!resposta.ok) throw new Error("HTTP " + resposta.status);
                const html = await resposta.text();
                if (!container.querySelector(".menu-lateral")) {
                    container.insertAdjacentHTML("afterbegin", html);
                }
            }

            const menu = container.querySelector(".menu-lateral");
            if (!menu) throw new Error("O arquivo carregado não contém o menu lateral.");

            const links = menu.querySelectorAll("nav a");
            const paginaAtual = container.dataset.page;
            menu.dataset.page = paginaAtual;
            const botaoMenu = menu.querySelector(".botao-menu");
            aplicarPerfil(menu, links, container, usuario);

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

            const linkVisivel = linksDaPagina.find(function (link) { return !link.hidden; });
            if (linkVisivel) {
                linkVisivel.classList.add("active");
                linkVisivel.setAttribute("aria-current", "page");
            }
        } catch (error) {
            console.error("Não foi possível carregar o menu lateral.", error);
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", carregarMenu, { once: true });
    } else {
        carregarMenu();
    }
}());
