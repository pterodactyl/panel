<template>
    <div>
        <flash container="mt-4"/>
        <div class="server-search animate fadein">
            <input type="text"
                   :placeholder="$t('dashboard.index.search')"
                   @input="onChange"
                   v-model="search"
                   ref="search"
            />
        </div>
        <div v-if="this.loading" class="my-4 animate fadein">
            <div class="text-center h-16">
                <span class="spinner spinner-xl"></span>
            </div>
        </div>
        <transition-group class="w-full m-auto mt-4 animate fadein sm:flex flex-wrap content-start" v-else>
            <server-box
                    v-for="(server, index) in servers.models"
                    v-bind:key="index"
                    v-bind:server="server"
                    v-bind:resources="resources[server.uuid]"
            />
        </transition-group>
    </div>
</template>

<script>
    import { ServerCollection } from '../../models/server';
    import _ from 'lodash';
    import Flash from '../Flash';
    import ServerBox from './ServerBox';

    export default {
        name: 'dashboard',
        components: { ServerBox, Flash },
        data: function () {
            return {
                loading: true,
                search: '',
                servers: new ServerCollection,
                resources: {},
            }
        },

        mounted: function () {
            this.loadServers();
        },

        methods: {
            /**
             * Load the user's servers and render them onto the dashboard.
             *
             * @param {string} query
             */
            loadServers: function (query = '') {
                this.loading = true;
                window.axios.get(this.route('api.client.index'), {
                    params: { query },
                })
                    .finally(() => {
                        this.clearFlashes();
                    })
                    .then(response => {
                        this.servers = new ServerCollection;
                        response.data.data.forEach(obj => {
                            this.resources[obj.attributes.uuid] = { cpu: 0, memory: 0 };
                            this.servers.add(obj.attributes);
                        });

                        if (this.servers.models.length === 0) {
                            this.info(this.$t('dashboard.index.no_matches'));
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        const response = err.response;
                        if (response.data && _.isObject(response.data.errors)) {
                            response.data.errors.forEach(function (error) {
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
            onChange: _.debounce(function () {
                this.loadServers(this.$data.search);
            }, 500),
        }
    };
</script>
