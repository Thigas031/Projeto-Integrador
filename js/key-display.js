(function () {
    function text(value) {
        return value === null || value === undefined ? "" : String(value).trim();
    }

    function name(key) {
        return text(key.name) || text(key.keyName) || text(key.roomName) || "Nome não informado";
    }

    function room(key) {
        return text(key.roomName);
    }

    function number(key) {
        const value = key.number !== undefined && key.number !== null ? key.number : key.keyNumber;
        return text(value);
    }

    function details(key) {
        const values = [];
        const roomName = room(key);
        const keyNumber = number(key);
        if (roomName) values.push(roomName);
        if (keyNumber) values.push("Nº " + keyNumber);
        return values.join(" · ");
    }

    function optionLabel(key) {
        const primary = name(key);
        const values = [];
        const roomName = room(key);
        const keyNumber = number(key);
        if (roomName && roomName !== primary) values.push(roomName);
        if (keyNumber) values.push("Nº " + keyNumber);
        return values.length ? primary + " — " + values.join(" · ") : primary;
    }

    function create(key) {
        const identity = document.createElement("div");
        identity.className = "key-identity";

        const title = document.createElement("strong");
        title.className = "key-identity-name";
        title.textContent = name(key);

        const meta = document.createElement("small");
        meta.className = "key-identity-meta";
        meta.textContent = details(key);
        meta.hidden = !meta.textContent;

        identity.append(title, meta);
        return identity;
    }

    window.AppKeyDisplay = {
        create: create,
        name: name,
        optionLabel: optionLabel
    };
}());
