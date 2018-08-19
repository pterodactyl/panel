import isFunction from 'lodash/isFunction';

export default new class SocketEmitter {
    constructor () {
        this.listeners = new Map();
    }

    /**
     * Add an event listener for socket events.
     *
     * @param {String|Number|Symbol} event
     * @param {Function} callback
     * @param {*} vm
     */
    addListener (event, callback, vm) {
        if (!isFunction(callback)) {
            return;
        }

        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }

        this.listeners.get(event).push({callback, vm});
    }

    /**
     * Remove an event listener for socket events based on the context passed through.
     *
     * @param {String|Number|Symbol} event
     * @param {Function} callback
     * @param {*} vm
     */
    removeListener (event, callback, vm) {
        if (!isFunction(callback) || !this.listeners.has(event)) {
            return;
        }

        const filtered = this.listeners.get(event).filter((listener) => {
            return listener.callback !== callback || listener.vm !== vm;
        });

        if (filtered.length > 0) {
            this.listeners.set(event, filtered);
        } else {
            this.listeners.delete(event);
        }
    }

    /**
     * Emit a socket event.
     *
     * @param {String|Number|Symbol} event
     * @param {Array} args
     */
    emit (event, ...args) {
        (this.listeners.get(event) || []).forEach((listener) => {
            listener.callback.call(listener.vm, ...args);
        });
    }
}
