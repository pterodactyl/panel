<template>
    <div>
        <div class="server-search animate fadein">
            <input type="text" placeholder="search for servers..."
                   @input="onChange"
                   v-model="search"
                   ref="search"
            />
        </div>
        <transition-group class="w-full m-auto mt-4 animate fadein sm:flex flex-wrap content-start"><div class="server-box" :key="index" v-for="(server, index) in servers.models">
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
                            <div class="indicator-title">CPU</div>
                        </div>
                        <div class="mb-4">
                            <div class="text-black font-bold text-xl">{{ server.name }}</div>
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

    export default {
        name: 'dashboard',
        data: function () {
            return {
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
                window.axios.get(this.route('api.client.index'), {
                    params: { query },
                })
                    .then(response => {
                        this.servers = new ServerCollection;
                        response.data.data.forEach(obj => {
                            this.servers.add(obj.attributes);
                        });
                    })
                    .catch(error => {
                        console.error(error);
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
