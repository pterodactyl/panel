<template>
    <div>
        <navigation/>
        <div class="container">
            <flash container="mt-4"/>
            <div class="server-search animate fadein">
                <input type="text"
                       :placeholder="$t('dashboard.index.search')"
                       @input="onChange"
                       v-model="search"
                       ref="search"
                />
            </div>
            <div v-if="this.isServersLoading" class="my-4 animate fadein">
                <div class="text-center h-16">
                    <span class="spinner spinner-xl"></span>
                </div>
            </div>
            <transition-group class="w-full m-auto mt-4 animate fadein sm:flex flex-wrap content-start">
                <server-box
                        v-for="(server, index) in this.serverList"
                        :key="index"
                        :server="server"
                />
            </transition-group>
        </div>
    </div>
</template>

<script>
    import { DateTime } from 'luxon';
    import _ from 'lodash';
    import Flash from '../Flash';
    import ServerBox from './ServerBox';
    import Navigation from '../core/Navigation';
    import {mapGetters, mapState} from 'vuex'

    export default {
        name: 'dashboard',
        components: { Navigation, ServerBox, Flash },
        data: function () {
            return {
                backgroundedAt: DateTime.local(),
                search: '',
            }
        },

        computed: {
            ...mapGetters([
                'isServersLoading',
                'serverList'
            ]),
            ...mapState({
                servers: 'servers'
            })
        },
        /**
         * Start loading the servers before the DOM $.el is created.
         */
        created: function () {
            this.$store.dispatch('loadServers')

            document.addEventListener('visibilitychange', () => {
                this.documentVisible = document.visibilityState === 'visible';
                this._handleDocumentVisibilityChange(this.documentVisible);
            });
        },

        /**
         * Once the page is mounted set a function to run every 5 seconds that will
         * iterate through the visible servers and fetch their resource usage.
         */
        mounted: function () {
            this._iterateServerResourceUse();
        },

        methods: {

            /**
             * Handle a search for servers but only call the search function every 500ms
             * at the fastest.
             */
            onChange: _.debounce(function () {
                this.loadServers(this.$data.search);
            }, 500),

            /**
             * Get resource usage for an individual server for rendering purposes.
             *
             * @param {Server} server
             */
            getResourceUse: function (server) {
                window.axios.get(this.route('api.client.servers.resources', { server: server.identifier }))
                    .then(response => {
                        if (!(response.data instanceof Object)) {
                            throw new Error('Received an invalid response object back from status endpoint.');
                        }

                        window.events.$emit(`server:${server.uuid}::resources`, response.data.attributes);
                    })
                    .catch(err => {
                        console.error(err);
                    });
            },

            /**
             * Iterates over all of the active servers and gets their resource usage.
             *
             * @private
             */
            _iterateServerResourceUse: function (initialTimeout = 5000) {
                window.setTimeout(() => {
                    if (this.documentVisible) {
                        return window.setTimeout(this._iterateServerResourceUse(), 5000);
                    }
                }, initialTimeout);
            },

            /**
             * Handle changes to document visibilty to keep server statuses updated properly.
             *
             * @param {Boolean} isVisible
             * @private
             */
            _handleDocumentVisibilityChange: function (isVisible) {
                if (!isVisible) {
                    this.backgroundedAt = DateTime.local();
                    return;
                }

                // If it has been more than 30 seconds since this window was put into the background
                // lets go ahead and refresh all of the listed servers so that they have fresh stats.
                const diff = DateTime.local().diff(this.backgroundedAt, 'seconds');
                this._iterateServerResourceUse(diff.seconds > 30 ? 1 : 5000);
            },
        }
    };
</script>
