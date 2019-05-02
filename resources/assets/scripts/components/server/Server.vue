<template>
    <div>
        <Navigation/>
        <Flash class="m-6"/>
        <div v-if="loadingServerData" class="container">
            <div class="mt-6 h-16">
                <div class="spinner spinner-xl spinner-thick blue"></div>
            </div>
        </div>
        <div v-else class="container">
            <div class="my-6 flex flex-no-shrink rounded animate fadein">
                <div class="sidebar flex-no-shrink w-1/3 max-w-xs">
                    <div class="mr-6">
                        <div class="p-6 text-center bg-white rounded shadow">
                            <h3 class="mb-2 text-primary-500 font-medium">{{server.name}}</h3>
                            <span class="text-neutral-600 text-sm">{{server.node}}</span>
                            <PowerButtons class="mt-6 pt-6 text-center border-t border-neutral-100"/>
                        </div>
                    </div>
                    <div class="sidenav mt-6 mr-6">
                        <ul>
                            <li>
                                <router-link :to="{ name: 'server', params: { id: $route.params.id } }">
                                    Console
                                </router-link>
                            </li>
                            <li>
                                <router-link :to="{ name: 'server-files' }">
                                    File Manager
                                </router-link>
                            </li>
                            <li>
                                <router-link :to="{ name: 'server-databases' }">
                                    Databases
                                </router-link>
                            </li>
                            <li>
                                <router-link :to="{ name: 'server-network' }">
                                    Networking
                                </router-link>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="h-full w-full">
                    <router-view :key="server.identifier"></router-view>
                </div>
            </div>
        </div>
        <div class="fixed pin-r pin-b m-6 max-w-sm" v-show="connectionError">
            <div class="alert error">
                There was an error while attempting to connect to the Daemon websocket. Error reported was: "{{connectionError.message}}"
            </div>
        </div>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Navigation from '@/components/core/Navigation.vue';
    import {mapState} from 'vuex';
    import {Socketio} from "@/mixins/socketio";
    import PowerButtons from "@/components/server/components/PowerButtons.vue";
    import Flash from "@/components/Flash.vue";

    export default Vue.extend({
        name: 'Server',
        components: {Flash, PowerButtons, Navigation},
        computed: {
            ...mapState('server', ['server', 'credentials']),
            ...mapState('socket', ['connected', 'connectionError']),
        },

        mixins: [Socketio],

        // Watch for route changes that occur with different server parameters. This occurs when a user
        // uses the search bar. Because of the way vue-router works, it won't re-mount the server component
        // so we will end up seeing the wrong server data if we don't perform this watch.
        watch: {
            '$route': function (toRoute, fromRoute) {
                if (toRoute.params.id !== fromRoute.params.id) {
                    this.loadingServerData = true;
                    this.loadServer();
                }
            }
        },

        data: function () {
            return {
                loadingServerData: true,
            };
        },

        mounted: function () {
            this.loadServer();
        },

        beforeDestroy: function () {
            this.removeSocket();
        },

        methods: {
            /**
             * Load the core server information needed for these pages to be functional.
             */
            loadServer: function () {
                Promise.all([
                    this.$store.dispatch('server/getServer', {server: this.$route.params.id}),
                    this.$store.dispatch('server/getCredentials', {server: this.$route.params.id})
                ])
                    .then(() => {
                        // Configure the websocket implementation and assign it to the mixin.
                        this.$socket().connect(
                            `ws://192.168.50.3:8080/api/servers/${this.server.uuid}/ws`,
                            'CC8kHCuMkXPosgzGO6d37wvhNcksWxG6kTrA',
                        );

                        this.loadingServerData = false;
                    })
                    .catch(err => {
                        console.error('There was an error performing Server::loadServer', {err});
                    });
            },
        },
    });
</script>
