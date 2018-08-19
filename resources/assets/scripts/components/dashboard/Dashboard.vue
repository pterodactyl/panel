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
    import debounce from 'lodash/debounce';
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
        },

        /**
         * Once the page is mounted set a function to run every 10 seconds that will
         * iterate through the visible servers and fetch their resource usage.
         */
        mounted: function () {
            this.$refs.search.focus();
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
        }
    };
</script>
