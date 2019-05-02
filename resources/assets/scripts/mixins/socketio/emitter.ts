import {isFunction} from 'lodash';
import {ComponentOptions} from "vue";
import {Vue} from "vue/types/vue";

export default new class SocketEmitter {
    listeners: Map<string | number, Array<{
        callback: (a: ComponentOptions<Vue>) => void,
        vm: ComponentOptions<Vue>,
    }>>;

    constructor() {
        this.listeners = new Map();
    }

    /**
     * Add an event listener for socket events.
     */
    addListener(event: string | number, callback: (...data: any[]) => void, vm: ComponentOptions<Vue>) {
        if (!isFunction(callback)) {
            return;
        }

        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }

        // @ts-ignore
        this.listeners.get(event).push({callback, vm});
    }

    /**
     * Remove an event listener for socket events based on the context passed through.
     */
    removeListener(event: string | number, callback: (...data: any[]) => void, vm: ComponentOptions<Vue>) {
        if (!isFunction(callback) || !this.listeners.has(event)) {
            return;
        }

        // @ts-ignore
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
     */
    emit(event: string | number, ...args: any) {
        (this.listeners.get(event) || []).forEach((listener) => {
            // @ts-ignore
            listener.callback.call(listener.vm, ...args);
        });
    }
}
