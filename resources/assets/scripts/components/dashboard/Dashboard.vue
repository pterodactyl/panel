<template>
    <div>
        <div class="server-search animate fadein">
            <input type="text" placeholder="search for servers..."
                   @input="onChange"
                   v-model="search"
                   ref="search"
            />
        </div>
        <transition-group class="w-full m-auto mt-4 animate fadein sm:flex flex-wrap content-start">
            <router-link class="server-box" :to="{name: 'server', params: { id: server.uuidShort }}" :key="index" v-for="(server, index) in servers">
                    <div class="content">
                        <div class="float-right">
                            <div class="indicator online"></div>
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
                        <div class="flex items-center">
                            <div class="text-sm">
                                <p class="text-grey">{{ server.node_name }}</p>
                                <p class="text-grey-dark">{{ server.allocation.ip }}:{{ server.allocation.port }}</p>
                            </div>
                        </div>
                    </div>
            </router-link>
        </transition-group>
    </div>
</template>

<script>
    import _ from 'lodash';

    export default {
        name: 'dashboard',
        data: function () {
            return {
                search: '',
                servers: [],
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
                const self = this;

                window.axios.get(this.route('dashboard.servers'), {
                    params: { query },
                })
                    .then(function (response) {
                        self.servers = response.data;
                    })
                    .catch(function (error) {
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
