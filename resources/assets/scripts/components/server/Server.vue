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
            <div class="main bg-white p-6 rounded border border-grey-lighter flex-grow">
                <router-view></router-view>
            </div>
        </div>
    </div>
</template>

<script>
    import { TerminalIcon, FolderIcon, UsersIcon, CalendarIcon, DatabaseIcon, GlobeIcon, SettingsIcon } from 'vue-feather-icons'
    import ServerConsole from "./ServerConsole";
    import Navigation from '../core/Navigation';
    import ProgressBar from './components/ProgressBar';
    import {mapState} from 'vuex';

    export default {
        components: {
            ProgressBar, Navigation, ServerConsole, TerminalIcon, FolderIcon, UsersIcon,
            CalendarIcon, DatabaseIcon, GlobeIcon, SettingsIcon
        },

        computed: {
            ...mapState('server', ['server']),
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
            loadServer: function () {
                this.$store.dispatch('server/getServer', {server: this.$route.params.id})
                    .then(() => {
                        this.loadingServerData = false;
                    })
                    .catch(err => {
                        console.error(err);
                    });
            },
        }
    }
</script>
