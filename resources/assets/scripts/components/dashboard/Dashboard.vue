<template>
    <div>
        <navigation/>
        <div class="container">
            <flash container="mt-4"/>
            <div class="server-search animate fadein">
                <input type="text"
                       :placeholder="$t('dashboard.index.search')"
                       @input="onChange"
                       v-model="searchTerm"
                       ref="search"
                />
            </div>
            <div v-if="this.loading" class="my-4 animate fadein">
                <div class="text-center h-16">
                    <span class="spinner spinner-xl spinner-thick blue"></span>
                </div>
            </div>
            <transition-group class="w-full m-auto mt-4 animate fadein sm:flex flex-wrap content-start" v-else>
                <server-box
                        v-for="(server, index) in servers"
                        v-bind:key="index"
                        v-bind:server="server"
                />
            </transition-group>
        </div>
    </div>
</template>

<script>
    import Server from '../../models/server';
    import debounce from 'lodash/debounce';
    import differenceInSeconds from 'date-fns/difference_in_seconds';
    import Flash from '../Flash';
    import ServerBox from './ServerBox';
    import Navigation from '../core/Navigation';
    import isObject from 'lodash/isObject';
    import {mapState} from 'vuex';

    export default {
        name: 'dashboard',
        components: { Navigation, ServerBox, Flash },
        data: function () {
            return {
                backgroundedAt: new Date(),
                documentVisible: true,
                loading: false,
            }
        },

        /**
         * Start loading the servers before the DOM $.el is created. If we already have servers
         * stored in vuex shows those and don't fire another API call just to load them again.
         */
        created: function () {
            if (this.servers.length === 0) {
                this.loadServers();
            }

            document.addEventListener('visibilitychange', () => {
                this.documentVisible = document.visibilityState === 'visible';
                this._handleDocumentVisibilityChange(this.documentVisible);
            });
        },

        /**
         * Once the page is mounted set a function to run every 10 seconds that will
         * iterate through the visible servers and fetch their resource usage.
         */
        mounted: function () {
            this.$refs.search.focus();
            
            window.setTimeout(() => {
                this._iterateServerResourceUse();
            }, 5000);
        },

        computed: {
            ...mapState('dashboard', ['servers']),
            searchTerm: {
                get: function () {
                    return this.$store.getters['dashboard/getSearchTerm'];
                },
                set: function (value) {
                    this.$store.dispatch('dashboard/setSearchTerm', value);
                }
            }
        },

        methods: {
            /**
             * Load the user's servers and render them onto the dashboard.
             */
            loadServers: function () {
                this.loading = true;
                this.$store.dispatch('dashboard/loadServers')
                    .finally(() => {
                        this.clearFlashes();
                    })
                    .then(() => {
                        if (this.servers.length === 0) {
                            this.info(this.$t('dashboard.index.no_matches'));
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        const response = err.response;
                        if (response.data && isObject(response.data.errors)) {
                            response.data.errors.forEach(error => {
                                this.error(error.detail);
                            });
                        }
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },

            /**
             * Handle a search for servers but only call the search function every 500ms
             * at the fastest.
             */
            onChange: debounce(function () {
                this.loadServers();
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
                    });
            },

            /**
             * Iterates over all of the active servers and gets their resource usage.
             *
             * @private
             */
            _iterateServerResourceUse: function (loop = true) {
                // Try again in 10 seconds, window is not in the foreground.
                if (!this.documentVisible && loop) {
                    window.setTimeout(() => {
                        this._iterateServerResourceUse();
                    }, 10000);
                }

                this.servers.forEach(server => {
                    this.getResourceUse(server);
                });

                if (loop) {
                    window.setTimeout(() => {
                        this._iterateServerResourceUse();
                    }, 10000);
                }
            },

            /**
             * Handle changes to document visibilty to keep server statuses updated properly.
             *
             * @param {Boolean} isVisible
             * @private
             */
            _handleDocumentVisibilityChange: function (isVisible) {
                if (!isVisible) {
                    this.backgroundedAt = new Date();
                    return;
                }

                // If it has been more than 30 seconds since this window was put into the background
                // lets go ahead and refresh all of the listed servers so that they have fresh stats.
                const diff = differenceInSeconds(new Date(), this.backgroundedAt);
                if (diff > 30) {
                    this._iterateServerResourceUse(false);
                }
            },
        }
    };
</script>
