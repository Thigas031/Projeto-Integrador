(function (global) {
    function request(url, options) {
        return fetch(url, options).then(async function (response) {
            let body;
            try {
                body = await response.json();
            } catch (error) {
                if (response.redirected) {
                    throw new Error("O servidor redirecionou a solicitação. Verifique se sua sessão ainda está ativa.");
                }
                if (response.status === 401) {
                    throw new Error("Sua sessão expirou. Faça login novamente.");
                }
                if (response.status === 403) {
                    throw new Error("Seu perfil não tem permissão para realizar esta operação.");
                }
                if (!response.ok) {
                    throw new Error("O servidor retornou HTTP " + response.status + " sem uma resposta JSON válida.");
                }
                throw new Error("O servidor retornou uma resposta inválida.");
            }

            if (!body || typeof body !== "object" || Array.isArray(body)) {
                throw new Error("O servidor retornou uma resposta JSON inválida.");
            }
            if (!response.ok || body.ok === false) {
                throw new Error(body.message || "Não foi possível concluir a solicitação.");
            }
            return body;
        });
    }

    function requestData(url, method, values) {
        const options = {
            method: method,
            credentials: "same-origin",
            headers: { "Accept": "application/json" }
        };
        let endpoint = url;

        if (method === "GET") {
            options.cache = "no-store";
            const query = new URLSearchParams(values || {}).toString();
            if (query) endpoint += "?" + query;
        } else {
            options.headers["Content-Type"] = "application/x-www-form-urlencoded;charset=UTF-8";
            options.body = new URLSearchParams(values || {}).toString();
        }

        return request(endpoint, options);
    }

    function apiData(url, method, values) {
        return requestData(url, method, values).then(function (body) {
            if (body.ok !== true) {
                throw new Error(body.message || "O servidor não confirmou a operação.");
            }
            if (!Object.prototype.hasOwnProperty.call(body, "data")) {
                throw new Error("A resposta do endpoint não contém o campo data.");
            }
            return body.data;
        });
    }

    function apiMutation(url, method, values) {
        return requestData(url, method, values).then(function (body) {
            if (body.ok !== true) {
                throw new Error(body.message || "O servidor não confirmou a operação.");
            }
            return Object.prototype.hasOwnProperty.call(body, "data") ? body.data : undefined;
        });
    }

    function requireProfile(allowedProfiles, message) {
        if (!global.AppProfile.isAllowed(allowedProfiles)) {
            throw new Error(message);
        }
    }

    function usuarioParaApi(values) {
        return {
            nome: values.name,
            cargo: values.role,
            cpf: values.cpf,
            telefone: values.phone || "",
            email: values.email
        };
    }

    function formatRole(role) {
        const labels = {
            COORDENACAO: "Coordenação",
            FUNCIONARIO: "Funcionário"
        };
        return labels[String(role || "").trim().toUpperCase()] || role;
    }

    const users = {
        async list() {
            requireProfile("GERENTE", "Somente GERENTE pode consultar a área administrativa de usuários.");
            return apiData("../controllers/controller_usuario_listar.php", "GET");
        },
        async listForMovement() {
            requireProfile("PORTEIRO", "Somente PORTEIRO pode identificar o responsável pela movimentação.");
            const options = await apiData("../controllers/controller_movimentacao_opcoes.php", "GET");
            return options.usuarios;
        },
        async create(values) {
            requireProfile("GERENTE", "Somente GERENTE pode cadastrar usuários.");
            return apiData("../controllers/controller_usuario_cadastrar.php", "POST", usuarioParaApi(values));
        },
        async update(id, values) {
            requireProfile("GERENTE", "Somente GERENTE pode editar usuários.");
            return apiMutation(
                "../controllers/controller_usuario_editar.php",
                "POST",
                Object.assign({ id: id }, usuarioParaApi(values))
            );
        },
        async setStatus(id, active) {
            requireProfile("GERENTE", "Somente GERENTE pode alterar o status dos usuários.");
            return apiMutation("../controllers/controller_usuario_status.php", "POST", {
                id: id,
                ativo: active ? "1" : "0"
            });
        },
        async delete(id) {
            return users.setStatus(id, false);
        }
    };

    const keys = {
        async list() {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para consultar as chaves.");
            const result = await keys.listPage({ pagina: 1 });
            return result.items;
        },
        async listPage(filters) {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para consultar as chaves.");
            const body = await requestData(
                "../controllers/controller_chave_listar.php",
                "GET",
                filters || { pagina: 1 }
            );
            return {
                items: body.data,
                total: body.total,
                page: body.pagina,
                pageSize: body.por_pagina
            };
        },
        async listReturnable() {
            requireProfile("PORTEIRO", "Somente PORTEIRO pode acessar o fluxo de devolução.");
            const movements = await movementsService.list();
            return movements.map(function (movement) {
                return {
                    id: movement.keyId,
                    name: movement.keyName,
                    number: movement.keyNumber,
                    roomName: movement.roomName,
                    block: movement.block,
                    status: movement.status,
                    responsible: movement.userName
                };
            });
        },
        async create(values) {
            requireProfile("GERENTE,PORTEIRO", "Somente GERENTE ou PORTEIRO pode cadastrar chaves.");
            return apiData("../controllers/controller_chave_cadastrar.php", "POST", {
                sala: values.roomId,
                nome_chave: values.name,
                numero_chave: values.number
            });
        },
        async get(id) {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para consultar esta chave.");
            return apiData("../controllers/controller_chave_listar.php", "GET", { chave: id });
        },
        async update(id, values) {
            requireProfile("GERENTE", "Somente GERENTE pode editar chaves.");
            return apiMutation("../controllers/controller_chave_editar.php", "POST", {
                id_chave: id,
                sala: values.roomId,
                nome_chave: values.name,
                numero_chave: values.number
            });
        },
        async delete(id) {
            requireProfile("GERENTE", "Somente GERENTE pode desativar chaves.");
            return apiData("../controllers/controller_chave_desativar.php", "POST", { id_chave: id });
        },
        async withdraw(id, userId, userName, userRole, notes, due, roomId) {
            requireProfile("PORTEIRO", "Somente PORTEIRO pode retirar chaves.");
            due = due || {};
            return apiData("../controllers/controller_movimentacao_retirar.php", "POST", {
                sala: roomId,
                usuario: userId,
                nome_retirante: userName,
                cargo_retirante: userRole,
                chave: id,
                data: due.withdrawnDate || "",
                hora: due.withdrawnTime || "",
                data_prevista: due.date,
                hora_prevista: due.time,
                observacao: notes || ""
            });
        },
        async returnKey(id) {
            requireProfile("PORTEIRO", "Somente PORTEIRO pode devolver chaves.");
            return apiData("../controllers/controller_movimentacao_devolver.php", "POST", { chave: id });
        }
    };

    const movementsService = {
        async list() {
            requireProfile("PORTEIRO", "Somente PORTEIRO pode consultar movimentações abertas.");
            return apiData("../controllers/controller_movimentacao_buscar.php", "GET", { abertas: "1" });
        },
        async find(filters) {
            requireProfile("PORTEIRO", "Somente PORTEIRO pode consultar movimentações.");
            return apiData("../controllers/controller_movimentacao_buscar.php", "GET", filters || {});
        },
        async options() {
            requireProfile("PORTEIRO", "Somente PORTEIRO pode carregar opções de retirada.");
            return apiData("../controllers/controller_movimentacao_opcoes.php", "GET");
        }
    };

    const occurrences = {
        async list(date) {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para consultar observações.");
            return apiData("../controllers/controller_observacao_listar.php", "GET", date ? { data: date } : {});
        },
        async create(values) {
            requireProfile("PORTEIRO", "Somente PORTEIRO pode registrar observações.");
            return apiData("../controllers/controller_observacao_salvar.php", "POST", { texto: values.text });
        }
    };

    const rooms = {
        async list() {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para consultar salas.");
            return apiData("../controllers/controller_sala_listar.php", "GET");
        },
        prepareWrite(values) {
            requireProfile("GERENTE", "Somente GERENTE pode administrar salas.");
            return {
                nome: String(values.name || "").trim(),
                numero: String(values.number || "").trim(),
                bloco: String(values.block || "").trim()
            };
        },
        async create(values) {
            requireProfile("GERENTE", "Somente GERENTE pode cadastrar salas.");
            return apiData("../controllers/controller_sala_cadastrar.php", "POST", values);
        },
        async update(id, values) {
            requireProfile("GERENTE", "Somente GERENTE pode editar salas.");
            return apiMutation("../controllers/controller_sala_editar.php", "POST", Object.assign({ id: id }, values));
        },
        async delete(id) {
            requireProfile("GERENTE", "Somente GERENTE pode excluir salas.");
            return apiMutation("../controllers/controller_sala_excluir.php", "POST", { id: id });
        }
    };

    const dashboard = {
        async summary() {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para consultar o dashboard.");
            return apiData("../controllers/controller_dashboard_resumo.php", "GET");
        }
    };

    const reports = {
        async generate(filters) {
            requireProfile("GERENTE", "Somente GERENTE pode gerar relatórios.");
            return apiData("../controllers/controller_relatorio_gerar.php", "GET", filters || {});
        },
        async history(filters) {
            requireProfile("GERENTE,PORTEIRO", "Perfil sem permissão para consultar o histórico.");
            return requestData("../controllers/controller_relatorio_gerar.php", "GET", filters || {});
        }
    };

    const auth = {
        async login(identifier, password, remember) {
            const body = new URLSearchParams({
                cpf: identifier,
                senha: password,
                lembrar: remember ? "1" : "0"
            });
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

    global.AppServices = {
        users,
        keys,
        movements: movementsService,
        occurrences,
        rooms,
        dashboard,
        reports,
        auth,
        formatRole
    };
}(window));
