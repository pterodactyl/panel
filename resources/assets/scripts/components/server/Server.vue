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
                    <div class="mt-6 p-4 text-center bg-white border rounded">
                        <button class="btn btn-red uppercase text-xs px-4 py-2">Stop</button>
                        <button class="btn btn-secondary uppercase text-xs px-4 py-2">Restart</button>
                        <button class="btn btn-secondary uppercase text-xs px-4 py-2">Kill</button>
                    </div>
                    <div class="mt-6 p-4 bg-white border rounded">
                        <progress-bar title="Memory" percent="33"></progress-bar>
                        <progress-bar title="CPU" percent="80" class="mt-4"></progress-bar>
                        <progress-bar title="Disk" percent="97" class="mt-4"></progress-bar>
                    </div>
                </div>
                <div class="sidenav">
                    <router-link :to="{ name: '', params: { id: this.$route.params.id } }">
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
            <div class="main bg-white p-6 rounded border border-grey-lighter flex-grow">
                <router-view></router-view>
            </div>
        </div>
    </div>
</template>

<script>
    import { TerminalIcon, FolderIcon, UsersIcon, CalendarIcon, DatabaseIcon, GlobeIcon, SettingsIcon } from 'vue-feather-icons'
    import Navigation from '../core/Navigation';
    import ProgressBar from './components/ProgressBar';
    import {mapState} from 'vuex';
    import { ConsolePage } from './subpages/ConsolePage';

    import io from 'socket.io-client';

    export default {
        components: {
            ProgressBar, Navigation, ConsolePage, TerminalIcon, FolderIcon, UsersIcon,
            CalendarIcon, DatabaseIcon, GlobeIcon, SettingsIcon
        },

        computed: {
            ...mapState('server', ['server', 'credentials']),
        },

        mounted: function () {
            this.loadServer();
        },

        data: function () {
            return {
                socket: null,
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
                        this.loadingServerData = false;
                        this.initalizeWebsocket();
                    })
                    .catch(console.error);
            },

            initalizeWebsocket: function () {
                this.$store.commit('server/CONSOLE_DATA', 'Connecting to ' + this.credentials.node + '...');
                this.socket = io(this.credentials.node + '/v1/ws/' + this.server.uuid, {
                    query: 'token=' + this.credentials.key,
                });

                this.socket.on('error', this._socket_error);
                this.socket.on('connect', this._socket_connect);
                this.socket.on('status', this._socket_status);
                this.socket.on('initial status', this._socket_status);
                this.socket.on('server log', this._socket_serverLog);
                this.socket.on('console', this._socket_consoleLine);
            },

            _socket_error: function (err) {
                this.$store.commit('server/CONSOLE_DATA', 'There was a socket error: ' + err);
                console.error('there was a socket error:', err);
            },

            _socket_connect: function () {
                this.$store.commit('server/CONSOLE_DATA', 'Connected to socket.');
                this.socket.emit('send server log');
                console.log('connected');
            },

            _socket_status: function (data) {
                this.$store.commit('server/CONSOLE_DATA', 'Server state has changed.');
                console.warn(data);
            },

            _socket_serverLog: function (data) {
                data.split(/\n/g).forEach(item => {
                    this.$store.commit('server/CONSOLE_DATA', item);
                });
            },

            _socket_consoleLine: function (data) {
                if(data.line) {
                    data.line.split(/\n/g).forEach((item) => {
                        this.$store.commit('server/CONSOLE_DATA', item);
                    });
                }
            }
        },
    }
</script>
