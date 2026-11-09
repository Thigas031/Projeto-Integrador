(function (global) {
    const supportedProfiles = new Set(["GERENTE", "PORTEIRO"]);
    let activeUser = null;

    function normalizeUser(user) {
        if (!user || !supportedProfiles.has(String(user.role).toUpperCase()) ||
            !user.id || !String(user.name || "").trim()) {
            return null;
        }

        return {
            id: user.id,
            name: String(user.name).trim(),
            role: String(user.role).toUpperCase()
        };
    }

    const ready = fetch("../controllers/controller_user_login.php", {
        method: "GET",
        credentials: "same-origin",
        headers: { "Accept": "application/json" }
    }).then(async function (response) {
        let body;
        try {
            body = await response.json();
        } catch (error) {
            throw new Error("O servidor de autenticação retornou uma resposta inválida.");
        }

        if (response.status === 401) {
            return null;
        }
        if (!response.ok || body.ok !== true) {
            throw new Error(body.message || "Não foi possível validar a sessão.");
        }

        activeUser = normalizeUser(body.user);
        if (!activeUser) {
            throw new Error("O servidor não retornou uma identificação válida para a sessão.");
        }
        return activeUser;
    });

    function get() {
        return activeUser;
    }

    function isAllowed(allowedProfiles) {
        if (!activeUser) return false;
        return allowedProfiles.split(",").map(function (item) {
            return item.trim().toUpperCase();
        }).includes(activeUser.role);
    }

    global.AppProfile = { ready: ready, get: get, isAllowed: isAllowed };
}(window));
