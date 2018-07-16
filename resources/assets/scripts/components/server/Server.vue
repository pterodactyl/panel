<template>
    <div>
        <navigation></navigation>
        <div class="m-6 flex flex-no-shrink rounded">
            <div class="sidebar border-grey-lighter flex-no-shrink w-1/3 max-w-xs">
                <div class="mr-6">
                    <div class="p-6 text-center bg-white border rounded">
                        <h3 class="mb-2 text-blue font-medium">Pterodactylcraft</h3>
                        <span class="text-grey-dark text-sm">Minecraft / Vanilla</span>
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
                <div class="pt-6 px-6 pb-4 text-center">
                    <!--<div class="mt-8 mb-6 text-grey-dark border-t border-grey-light usage">-->
                        <!--<div class="mt-8 mb-6 text-grey-dark border-t border-grey-light usage">-->
                            <!--<span class="bg-grey-lighter">CPU - 2 Cores</span>-->
                            <!--<div class="rounded border-grey-light border mt-3 h-4">-->
                                <!--<div class="rounded bg-blue h-4 w-1/6"></div>-->
                            <!--</div>-->
                        <!--</div>-->
                        <!--<div class="my-6 text-grey-dark border-t border-grey-light usage">-->
                            <!--<span class="bg-grey-lighter">RAM - 4 GB</span>-->
                            <!--<div class="rounded border-grey-light border mt-3 h-4">-->
                                <!--<div class="rounded bg-blue h-4 w-2/3"></div>-->
                            <!--</div>-->
                        <!--</div>-->
                        <!--<div class="my-6 text-grey-dark border-t border-grey-light usage">-->
                            <!--<span class="bg-grey-lighter">Disk - 20 GB</span>-->
                            <!--<div class="rounded border-grey-light border mt-3 h-4">-->
                                <!--<div class="rounded bg-blue h-4 w-1/3"></div>-->
                            <!--</div>-->
                        <!--</div>-->
                    <!--</div>-->
                </div>
                <div class="sidenav">
                    <router-link :to="{ name: 'server', params: { serverID: this.$route.params.serverID } }">
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
                    <!--h1.text-blue.mb-6 Server Console-->
                    <router-view></router-view>
                </div>
            </div>
    </div>
</template>

<script>
    import { TerminalIcon, OctagonIcon, FolderIcon, UsersIcon, CalendarIcon, DatabaseIcon, GlobeIcon, SettingsIcon } from 'vue-feather-icons'
    import ServerConsole from "./ServerConsole";
    import Navigation from '../core/Navigation';
    import ProgressBar from './components/ProgressBar';

    export default {
        components: {
            ProgressBar,
            OctagonIcon, Navigation, ServerConsole, TerminalIcon, FolderIcon, UsersIcon,
            CalendarIcon, DatabaseIcon, GlobeIcon, SettingsIcon
        }
    }
</script>
