(function (global) {
    function showAccessError(message) {
        document.body.replaceChildren();
        const notice = document.createElement("main");
        notice.setAttribute("role", "alert");
        notice.style.cssText = "max-width:40rem;margin:10vh auto;padding:2rem;font:16px/1.5 system-ui,sans-serif";
        const text = document.createElement("p");
        text.textContent = message;
        const link = document.createElement("a");
        link.href = "login.html";
        link.textContent = "Ir para o Login";
        notice.append(text, link);
        document.body.appendChild(notice);
        document.body.dataset.authState = "error";
    }

    function run(onAuthorized) {
        return global.AppProfile.ready.then(function (user) {
            if (!user) {
                global.location.replace("login.html");
                return false;
            }

            const allowedProfiles = document.body.dataset.profiles;
            if (allowedProfiles && !global.AppProfile.isAllowed(allowedProfiles)) {
                global.location.replace("dashboard.php");
                return false;
            }

            document.body.dataset.authState = "ready";
            onAuthorized(user);
            return true;
        }).catch(function (error) {
            console.error("Não foi possível validar o acesso à página.", error);
            showAccessError(error.message || "Não foi possível validar sua sessão. O acesso foi bloqueado.");
            return false;
        });
    }

    global.AppAccess = { run: run };
}(window));
