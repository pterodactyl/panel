<template>
    <div>
        <navigation></navigation>
        <div v-if="loadingServerData">
            <div class="mt-6 h-16">
                <div class="spinner spinner-xl spinner-thick blue"></div>
            </div>
        </div>
        <div class="m-6 flex flex-no-shrink rounded animate fadein" v-else>
            <div class="sidebar border-grey-lighter flex-no-shrink w-1/3 max-w-xs">
                <div class="mr-6">
                    <div class="p-6 text-center bg-white border rounded">
                        <h3 class="mb-2 text-blue font-medium">{{server.name}}</h3>
                        <span class="text-grey-dark text-sm">{{server.node}}</span>
                    </div>
                    <power-buttons class="mt-6 p-4 text-center bg-white border rounded"/>
                    <div class="mt-6 p-4 bg-white border rounded">
                        <progress-bar title="Memory" percent="33"></progress-bar>
                        <progress-bar title="CPU" percent="80" class="mt-4"></progress-bar>
                        <progress-bar title="Disk" percent="97" class="mt-4"></progress-bar>
                    </div>
                </div>
                <div class="sidenav">
                    <router-link :to="{ name: 'server', params: { id: this.$route.params.id } }">
                        <terminal-icon style="height: 1em;"></terminal-icon>
                        Console
                    </router-link>
                    <router-link :to="{ name: 'server-files' }">
                        <folder-icon style="height: 1em;"></folder-icon>
                        Files
                    </router-link>
                    <router-link :to="{ name: 'server-subusers' }">
                        <users-icon style="height: 1em;"></users-icon>
                        Subusers
                    </router-link>
                    <router-link :to="{ name: 'server-schedules' }">
                        <calendar-icon style="height: 1em;"></calendar-icon>
                        Schedules
                    </router-link>
                    <router-link :to="{ name: 'server-databases' }">
                        <database-icon style="height: 1em;"></database-icon>
                        Databases
                    </router-link>
                    <router-link :to="{ name: 'server-allocations' }">
                        <globe-icon style="height: 1em;"></globe-icon>
                        Allocations
                    </router-link>
                    <router-link :to="{ name: 'server-settings' }">
                        <settings-icon style="height: 1em;"></settings-icon>
                        Settings
                    </router-link>
                </div>
            </div>
            <div class="bg-white p-6 rounded border border-grey-light h-full w-full">
                <router-view></router-view>
            </div>
        </div>
    </div>
</template>

<script>
    import { TerminalIcon, FolderIcon, UsersIcon, CalendarIcon, DatabaseIcon, GlobeIcon, SettingsIcon } from 'vue-feather-icons'
    import Navigation from '../core/Navigation';
    import ProgressBar from './components/ProgressBar';
    import { mapState } from 'vuex';
    import VueSocketio from 'vue-socket.io-extended';
    import io from 'socket.io-client';
    import Vue from 'vue';

    import PowerButtons from './components/PowerButtons';

    export default {
        components: {
            PowerButtons, ProgressBar, Navigation,
            TerminalIcon, FolderIcon, UsersIcon, CalendarIcon, DatabaseIcon, GlobeIcon, SettingsIcon
        },

        computed: {
            ...mapState('server', ['server', 'credentials']),
            ...mapState('socket', ['connected', 'connectionError']),
        },

        mounted: function () {
            this.loadServer();
        },

        data: function () {
            return {
                loadingServerData: true,
            };
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

                        Vue.use(VueSocketio, socket, { store: this.$store });
                        this.loadingServerData = false;
                    })
                    .catch(console.error);
            },
        },
    }
</script>
