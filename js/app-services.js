(function (global) {
    function request(url, options) {
        return fetch(url, options).then(async function (response) {
            let body;
            try {
                body = await response.json();
            } catch (error) {
                throw new Error("O servidor retornou uma resposta inválida.");
            }

            if (!response.ok || body.ok === false) {
                throw new Error(body.message || "Não foi possível concluir a solicitação.");
            }
            return body;
        });
    }

    function apiData(action, method, payload) {
        const endpoints = global.APP_CONFIG && global.APP_CONFIG.endpoints;
        const endpoint = endpoints && endpoints[action];
        if (!endpoint) {
            return Promise.reject(new Error("O endpoint PHP para '" + action + "' ainda não foi configurado."));
        }

        const values = Object.assign({}, payload || {});
        const id = values.id;
        delete values.id;
        const url = String(endpoint).replace("{id}", encodeURIComponent(id || ""));
        const options = {
            method: method,
            credentials: "same-origin",
            headers: { "Accept": "application/json" }
        };
        if (method !== "GET" && method !== "DELETE") {
            options.headers["Content-Type"] = "application/json;charset=UTF-8";
            options.body = JSON.stringify(values);
        }

        return request(url, options).then(function (body) {
            if (!Object.prototype.hasOwnProperty.call(body, "data")) {
                throw new Error("A resposta do endpoint '" + action + "' não contém o campo data.");
            }
            return body.data;
        });
    }

    function requireProfile(allowedProfiles, message) {
        if (!global.AppProfile.isAllowed(allowedProfiles)) {
            throw new Error(message);
        }
    }

    const users = {
        async list() {
            requireProfile("GERENTE", "Somente GERENTE pode consultar a área administrativa de usuários.");
            return apiData("listUsers", "GET");
        },
        async listForMovement() {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para identificar o responsável pela movimentação.");
            return apiData("listMovementUsers", "GET");
        },
        async create(values) {
            requireProfile("GERENTE", "Somente GERENTE pode cadastrar usuários.");
            return apiData("createUser", "POST", values);
        },
        async update(id, values) {
            requireProfile("GERENTE", "Somente GERENTE pode editar usuários.");
            return apiData("updateUser", "PUT", Object.assign({ id: id }, values));
        },
        async delete(id) {
            requireProfile("GERENTE", "Somente GERENTE pode excluir usuários.");
            return apiData("deleteUser", "DELETE", { id: id });
        }
    };

    const keys = {
        async list() {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para consultar as chaves.");
            return apiData("listKeys", "GET");
        },
        async listReturnable() {
            requireProfile("PORTEIRO", "Somente PORTEIRO pode acessar o fluxo de devolução.");
            return apiData("listReturnableKeys", "GET");
        },
        async create(values) {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para cadastrar chaves.");
            return apiData("createKey", "POST", values);
        },
        async get(id) {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para consultar esta chave.");
            return apiData("getKey", "GET", { id: id });
        },
        async update(id, values) {
            requireProfile("GERENTE", "Somente GERENTE pode editar chaves.");
            return apiData("updateKey", "PUT", Object.assign({ id: id }, values));
        },
        async delete(id) {
            requireProfile("GERENTE", "Somente GERENTE pode excluir chaves.");
            return apiData("deleteKey", "DELETE", { id: id });
        },
        async withdraw(id, userId, notes) {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para movimentar chaves.");
            return apiData("withdrawKey", "POST", { keyId: id, userId: userId, notes: notes });
        },
        async returnKey(id) {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para movimentar chaves.");
            return apiData("returnKey", "POST", { id: id });
        }
    };

    const movements = {
        async list() {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para consultar movimentações.");
            return apiData("listMovements", "GET");
        }
    };

    const occurrences = {
        async list() {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para consultar ocorrências.");
            return apiData("listOccurrences", "GET");
        },
        async create(values) {
            requireProfile("PORTEIRO", "Somente o perfil PORTEIRO pode registrar ocorrências.");
            return apiData("createOccurrence", "POST", values);
        }
    };

    const auth = {
        async login(identifier, password) {
            const body = new URLSearchParams({ identificador: identifier, senha: password });
            return request("../controllers/controller_user_login.php", {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
                },
                body: body.toString(),
                credentials: "same-origin"
            });
        },
        async logout() {
            const body = new URLSearchParams({ acao: "logout" });
            return request("../controllers/controller_user_login.php", {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
                },
                body: body.toString(),
                credentials: "same-origin"
            });
        }
    };

    global.AppServices = { users, keys, movements, occurrences, auth };
}(window));
