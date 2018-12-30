<template>
    <div>
        <navigation></navigation>
        <flash class="m-6"/>
        <div v-if="loadingServerData">
            <div class="mt-6 h-16">
                <div class="spinner spinner-xl spinner-thick blue"></div>
            </div>
        </div>
        <div v-else>
            <div class="m-6 flex flex-no-shrink rounded animate fadein">
                <div class="sidebar border-grey-lighter flex-no-shrink w-1/3 max-w-xs">
                    <div class="mr-6">
                        <div class="p-6 text-center bg-white border rounded">
                            <h3 class="mb-2 text-blue font-medium">{{server.name}}</h3>
                            <span class="text-grey-dark text-sm">{{server.node}}</span>
                            <power-buttons class="mt-6 pt-6 text-center border-t border-grey-lighter"/>
                        </div>
                    </div>
                    <div class="mt-6 sidenav mr-6 bg-white border rounded">
                        <router-link :to="{ name: 'server', params: { id: $route.params.id } }">
                            <terminal-icon class="h-4"></terminal-icon> Console
                        </router-link>
                        <router-link :to="{ name: 'server-files' }">
                            <folder-icon class="h-4"></folder-icon> Files
                        </router-link>
                        <router-link :to="{ name: 'server-subusers' }">
                            <users-icon class="h-4"></users-icon> Subusers
                        </router-link>
                        <router-link :to="{ name: 'server-schedules' }">
                            <calendar-icon class="h-4"></calendar-icon> Schedules
                        </router-link>
                        <router-link :to="{ name: 'server-databases' }">
                            <database-icon class="h-4"></database-icon> Databases
                        </router-link>
                        <router-link :to="{ name: 'server-allocations' }">
                            <globe-icon class="h-4"></globe-icon> Allocations
                        </router-link>
                        <router-link :to="{ name: 'server-settings' }">
                            <settings-icon class="h-4"></settings-icon> Settings
                        </router-link>
                    </div>
                </div>
                <div class="h-full w-full">
                    <!--
                    <div class="mb-6 bg-white border rounded" v-if="$router.currentRoute.name !== 'server'">
                        <div class="flex">
                            <progress-bar title="Memory" percent="33" class="flex-1 p-4 pb-6"></progress-bar>
                            <progress-bar title="CPU" percent="80" class="flex-1 p-4 pb-6"></progress-bar>
                            <progress-bar title="Disk" percent="97" class="flex-1 p-4 pb-6"></progress-bar>
                        </div>
                    </div>
                    -->
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

<script>
    import { TerminalIcon, FolderIcon, UsersIcon, CalendarIcon, DatabaseIcon, GlobeIcon, SettingsIcon } from 'vue-feather-icons'
    import Navigation from '../core/Navigation';
    import ProgressBar from './components/ProgressBar';
    import { mapState } from 'vuex';
    import io from 'socket.io-client';

    import PowerButtons from './components/PowerButtons';
    import Flash from '../Flash';

    export default {
        name: 'server',
        components: {
            Flash, PowerButtons, ProgressBar, Navigation,
            TerminalIcon, FolderIcon, UsersIcon, CalendarIcon, DatabaseIcon, GlobeIcon, SettingsIcon,
        },

        computed: {
            ...mapState('server', ['server', 'credentials']),
            ...mapState('socket', ['connected', 'connectionError']),
        },

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
                        // Configure the socket.io implementation. This is a really ghetto way of handling things
                        // but all of these plugins assume you have some constant connection, which we don't.
                        const socket = io(`${this.credentials.node}/v1/ws/${this.server.uuid}`, {
                            query: `token=${this.credentials.key}`,
                        });

                        this.$socket().connect(socket);
                        this.loadingServerData = false;
                    })
                    .catch(err => {
                       console.error({ err });
                    });
            },
        },
    }
</script>
