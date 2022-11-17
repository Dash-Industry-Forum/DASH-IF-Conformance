function EventHandler() {
    let callbacks = {};

    function on(eventName, callback) {
        if (!callbacks[eventName]) callbacks[eventName] = [];
        callbacks[eventName].push(callback);
    }

    function off(eventName, callback) {
        if (!callbacks[eventName]) return;
        let index = callbacks[eventName].findIndex(element => element === callback);
        callbacks[eventName].splice(index, 1);
    }

    function dispatchEvent(eventName, payload) {
        if (!callbacks[eventName]) return;
        callbacks[eventName].forEach(callback => callback(payload));
    }

    let instance = {
        on,
        off,
        dispatchEvent,
    };
    return instance;
}