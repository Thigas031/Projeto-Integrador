/* Dados locais temporários para demonstrar as telas até a integração com o backend. */
(function () {
    const storageKey = "gerenciamento-chaves-demo";
    const defaults = {
        keys: [
            { id: "001", room: "Sala 01", status: "disponivel" },
            { id: "002", room: "Sala 02", status: "emprestada", user: "Carlos Nogueira", date: "21/10/2026", time: "09:15" },
            { id: "003", room: "Laboratório", status: "atrasada", user: "Vinícius Dantas", date: "20/10/2026", time: "14:30" },
            { id: "005", room: "Inovação", status: "emprestada", user: "Jamilton Neto", date: "21/10/2026", time: "10:00" }
        ]
    };

    function clone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function read() {
        try {
            return JSON.parse(localStorage.getItem(storageKey)) || clone(defaults);
        } catch (_) {
            return clone(defaults);
        }
    }

    function save(data) {
        localStorage.setItem(storageKey, JSON.stringify(data));
    }

    function returnKey(id) {
        const data = read();
        const key = data.keys.find(function (item) {
            return item.id === id;
        });

        if (!key) return;

        key.status = "disponivel";
        delete key.user;
        delete key.date;
        delete key.time;
        save(data);
    }

    window.DemoKeys = { read, save, returnKey };
}());
