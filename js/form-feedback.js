(function (global) {
    function show(element, message, type) {
        element.textContent = message;
        element.classList.remove("show", "error", "success");
        element.classList.add("show", type);
    }

    function clear(element) {
        element.textContent = "";
        element.classList.remove("show", "error", "success");
    }

    global.AppFeedback = { show, clear };
}(window));