import SocketEmitter from './emitter';
import SocketioConnector from './connector';
import {ComponentOptions} from 'vue';
import {Vue} from "vue/types/vue";

let connector: SocketioConnector | null = null;

export const Socketio: ComponentOptions<Vue> = {
    /**
     * Setup the socket when we create the first component using the mixin. This is the Server.vue
     * file, unless you mess up all of this code. Subsequent components to use this mixin will
     * receive the existing connector instance, so it is very important that the top-most component
     * calls the connectors destroy function when it is destroyed.
     */
    created: function () {
        if (!connector) {
            connector = new SocketioConnector(this.$store);
        }

        const sockets = (this.$options || {}).sockets || {};
        Object.keys(sockets).forEach((event) => {
            SocketEmitter.addListener(event, sockets[event], this);
        });
    },

    /**
     * Before destroying the component we need to remove any event listeners registered for it.
     */
    beforeDestroy: function () {
        const sockets = (this.$options || {}).sockets || {};
        Object.keys(sockets).forEach((event) => {
            SocketEmitter.removeListener(event, sockets[event], this);
        });
    },

    methods: {
        /**
         * @return {SocketioConnector}
         */
        '$socket': function () {
            return connector;
        },

        /**
         * Disconnects from the active socket and sets the connector to null.
         */
        removeSocket: function () {
            if (!connector) {
                return;
            }

            const instance: SocketIOClient.Socket | null = connector.instance();
            if (instance) {
                instance.close();
            }

            connector = null;
        },
    },
};
