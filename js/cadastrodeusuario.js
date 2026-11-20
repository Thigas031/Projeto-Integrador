(function () {
window.AppAccess.run(function () {
    const form = document.getElementById("user-form");
    const feedback = document.getElementById("user-feedback");
    const listFeedback = document.getElementById("users-list-feedback");
    const tableBody = document.getElementById("users-table-body");
    const emptyRow = document.getElementById("users-empty");
    const newUserButton = document.getElementById("new-user");
    const cancelButton = document.getElementById("cancel-user-edit");
    const title = document.getElementById("user-form-title");
    const editContext = document.getElementById("user-edit-context");
    const saveButton = document.getElementById("save-user");
    const fields = {
        name: document.getElementById("user-name"),
        role: document.getElementById("user-role"),
        cpf: document.getElementById("user-cpf"),
        phone: document.getElementById("user-phone"),
        email: document.getElementById("user-email")
    };
    let users = [];
    let editingId = "";

    fields.role.replaceChildren();
    [
        { value: "", label: "Selecione o cargo" },
        { value: "GERENTE", label: "Gerente Geral" },
        { value: "PORTEIRO", label: "Porteiro" },
        { value: "PROFESSOR", label: "Professor" },
        { value: "OUTRO", label: "Outro" }
    ].forEach(function (cargo) {
        const option = document.createElement("option");
        option.value = cargo.value;
        option.textContent = cargo.label;
        fields.role.appendChild(option);
    });

    function formatCpf(value) {
        const digits = value.replace(/\D/g, "").slice(0, 11);
        if (digits.length <= 3) return digits;
        if (digits.length <= 6) return digits.slice(0, 3) + "." + digits.slice(3);
        if (digits.length <= 9) return digits.slice(0, 3) + "." + digits.slice(3, 6) + "." + digits.slice(6);
        return digits.slice(0, 3) + "." + digits.slice(3, 6) + "." + digits.slice(6, 9) + "-" + digits.slice(9);
    }

    function createCell(text) {
        const cell = document.createElement("td");
        cell.textContent = text || "—";
        return cell;
    }

    function startEditing(user) {
        editingId = user ? user.id : "";
        title.textContent = user ? "Editar usuário" : "Cadastrar usuário";
        editContext.textContent = user ? "Editando: " + user.name + " · " + (user.role || "Cargo não informado") : "";
        editContext.hidden = !user;
        form.classList.toggle("user-form--editing", Boolean(user));
        saveButton.textContent = user ? "Salvar alterações" : "Cadastrar usuário";
        form.reset();
        if (user) {
            fields.name.value = user.name;
            fields.role.value = user.role;
            fields.cpf.value = formatCpf(user.cpf);
            fields.phone.value = user.phone || "";
            fields.email.value = user.email;
        }
        form.hidden = false;
        form.scrollIntoView({ behavior: "smooth", block: "start" });
        window.AppFeedback.clear(feedback);
    }

    function renderUsers() {
        tableBody.replaceChildren();
        if (!users.length) {
            emptyRow.hidden = false;
            tableBody.appendChild(emptyRow);
            return;
        }

        users.forEach(function (user) {
            const row = document.createElement("tr");
            row.append(
                createCell(user.name),
                createCell(formatCpf(user.cpf)),
                createCell({
                    GERENTE: "Gerente Geral",
                    PORTEIRO: "Porteiro",
                    PROFESSOR: "Professor",
                    OUTRO: "Outro"
                }[user.role] || user.role),
                createCell(user.phone),
                createCell(user.email)
            );
            const actions = document.createElement("td");
            const editButton = document.createElement("button");
            editButton.type = "button";
            editButton.textContent = "Editar";
            editButton.addEventListener("click", function () { startEditing(user); });
            const deleteButton = document.createElement("button");
            deleteButton.type = "button";
            deleteButton.className = user.active ? "danger" : "";
            deleteButton.textContent = user.active ? "Desativar" : "Ativar";
            deleteButton.addEventListener("click", async function () {
                const novoStatus = !user.active;
                const acao = novoStatus ? "ativar" : "desativar";
                if (!window.confirm("Deseja " + acao + " o usuário " + user.name + "?")) return;
                try {
                    await window.AppServices.users.setStatus(user.id, novoStatus);
                    window.AppFeedback.show(listFeedback, "Status do usuário atualizado.", "success");
                    await loadUsers(true);
                } catch (error) {
                    window.AppFeedback.show(listFeedback, error.message || "Não foi possível excluir o usuário.", "error");
                }
            });
            actions.append(editButton, deleteButton);
            row.appendChild(actions);
            tableBody.appendChild(row);
        });
    }

    async function loadUsers(preserveFeedback) {
        tableBody.replaceChildren();
        const loadingRow = document.createElement("tr");
        const loadingCell = document.createElement("td");
        loadingCell.colSpan = 6;
        loadingCell.textContent = "Carregando usuários...";
        loadingRow.appendChild(loadingCell);
        tableBody.appendChild(loadingRow);
        try {
            users = await window.AppServices.users.list();
            if (!preserveFeedback) window.AppFeedback.clear(listFeedback);
            renderUsers();
        } catch (error) {
            tableBody.replaceChildren();
            const errorRow = document.createElement("tr");
            const errorCell = document.createElement("td");
            errorCell.colSpan = 6;
            errorCell.textContent = "Não foi possível carregar os usuários.";
            errorRow.appendChild(errorCell);
            tableBody.appendChild(errorRow);
            window.AppFeedback.show(listFeedback, error.message || "Não foi possível carregar os usuários.", "error");
        }
    }

    fields.cpf.addEventListener("input", function () {
        fields.cpf.value = formatCpf(fields.cpf.value);
    });

    newUserButton.addEventListener("click", function () { startEditing(null); });
    cancelButton.addEventListener("click", function () {
        form.reset();
        form.hidden = true;
        editingId = "";
        editContext.hidden = true;
        form.classList.remove("user-form--editing");
        saveButton.textContent = "Cadastrar usuário";
        window.AppFeedback.clear(feedback);
    });

    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        const cpf = fields.cpf.value.replace(/\D/g, "");
        if (!form.checkValidity()) {
            form.reportValidity();
            window.AppFeedback.show(feedback, "Preencha os campos obrigatórios corretamente.", "error");
            return;
        }
        if (cpf.length !== 11) {
            window.AppFeedback.show(feedback, "Informe um CPF completo com 11 números.", "error");
            fields.cpf.focus();
            return;
        }

        const phone = fields.phone.value.trim();
        const phoneDigits = phone.replace(/\D/g, "");
        if (!/^[0-9()+. \-]+$/.test(phone) || (phone && (phoneDigits.length < 10 || phoneDigits.length > 11))) {
            window.AppFeedback.show(feedback, "Informe um telefone com DDD e número válido.", "error");
            return;
        }

        const values = {
            name: fields.name.value.trim(),
            role: fields.role.value,
            cpf: cpf,
            phone: phone,
            email: fields.email.value.trim()
        };
        try {
            let successMessage;
            if (editingId) {
                await window.AppServices.users.update(editingId, values);
                successMessage = "Usuário atualizado com sucesso.";
            } else {
                await window.AppServices.users.create(values);
                successMessage = "Usuário cadastrado com sucesso.";
            }
            form.reset();
            form.hidden = true;
            editingId = "";
            window.AppFeedback.show(listFeedback, successMessage, "success");
            await loadUsers(true);
        } catch (error) {
            window.AppFeedback.show(feedback, error.message || "Não foi possível salvar o usuário.", "error");
        }
    });

    form.hidden = true;
    loadUsers();
});
}());
