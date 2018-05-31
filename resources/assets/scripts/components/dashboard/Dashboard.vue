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
            <div class="server-box animate fadein" :key="index" v-for="(server, index) in servers.models">
                <router-link :to="{ name: 'server', params: { id: server.identifier }}" class="content">
                    <div class="float-right">
                        <div class="indicator"></div>
                    </div>
                    <div class="mb-4">
                        <div class="text-black font-bold text-xl">
                            {{ server.name }}
                        </div>
                    </div>
                    <div class="mb-0 flex">
                        <div class="usage">
                            <div class="indicator-title">{{ $t('dashboard.index.cpu_title') }}</div>
                        </div>
                        <div class="usage">
                            <div class="indicator-title">{{ $t('dashboard.index.memory_title') }}</div>
                        </div>
                        <div class="mb-0 flex">
                            <div class="usage">
                                <div class="indicator-title">CPU</div>
                            </div>
                            <div class="usage">
                                <div class="indicator-title">Memory</div>
                            </div>
                        </div>
                        <div class="mb-4 flex text-center">
                            <div class="inline-block border border-grey-lighter border-l-0 p-4 flex-1">
                                <span class="font-bold text-xl">---</span>
                                <span class="font-light text-sm">%</span>
                            </div>
                            <div class="inline-block border border-grey-lighter border-l-0 border-r-0 p-4 flex-1">
                                <span class="font-bold text-xl">---</span>
                                <span class="font-light text-sm">Mb</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="text-sm">
                            <p class="text-grey">{{ server.node }}</p>
                            <p class="text-grey-dark">{{ server.allocation.ip }}:{{ server.allocation.port }}</p>
                        </div>
                    </div>
                </router-link>
            </div>
        </transition-group>
    </div>
</template>

<script>
    import { ServerCollection } from '../../models/server';
    import _ from 'lodash';
    import Flash from '../Flash';

    export default {
        name: 'dashboard',
        components: { Flash },
        data: function () {
            return {
                loading: true,
                search: '',
                servers: new ServerCollection,
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
