<template>
    <div>
        <navigation></navigation>
        <div class="p-2 flex flex-no-shrink rounded">
            <div class="sidebar border-grey-lighter flex-no-shrink w-1/3 max-w-xs">
                <div class="pt-6 px-6 pb-4 text-center">
                    <h3 class="mb-2 text-blue">Pterodactylcraft</h3>
                    <span class="text-grey-dark">Minecraft / Vanilla</span>
                    <div class="text-lg my-4"><span class="text-green">●</span> Running</div>
                    <div>
                        <button class="btn btn-red">Stop</button>
                        <button class="btn btn-blue">Restart</button>
                        <button class="btn btn-red">Kill</button>
                    </div>
                    <div class="mt-8 mb-6 text-grey-dark border-t border-grey-light usage">
                        <span class="bg-grey-lighter">CPU - 2 Cores</span>
                        <div class="rounded border-grey-light border mt-3 h-4">
                            <div class="rounded bg-blue h-4 w-1/6"></div>
                        </div>
                        <div class="mt-8 mb-6 text-grey-dark border-t border-grey-light usage">
                            <span class="bg-grey-lighter">CPU - 2 Cores</span>
                            <div class="rounded border-grey-light border mt-3 h-4">
                                <div class="rounded bg-blue h-4 w-1/6"></div>
                            </div>
                        </div>
                        <div class="my-6 text-grey-dark border-t border-grey-light usage">
                            <span class="bg-grey-lighter">RAM - 4 GB</span>
                            <div class="rounded border-grey-light border mt-3 h-4">
                                <div class="rounded bg-blue h-4 w-2/3"></div>
                            </div>
                        </div>
                        <div class="my-6 text-grey-dark border-t border-grey-light usage">
                            <span class="bg-grey-lighter">Disk - 20 GB</span>
                            <div class="rounded border-grey-light border mt-3 h-4">
                                <div class="rounded bg-blue h-4 w-1/3"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sidenav">
                    <router-link :to="{ name: 'server' }">
                        <font-awesome-icon class="mr-2" fixed-with icon="terminal"/>
                        Console
                    </router-link>
                    <router-link :to="{ name: 'server-files' }">
                        <font-awesome-icon class="mr-2" fixed-with icon="folder-open"/>
                        Files
                    </router-link>
                    <router-link :to="{ name: 'server-subusers' }">
                        <font-awesome-icon class="mr-2" fixed-with icon="users"/>
                        Subusers
                    </router-link>
                    <router-link :to="{ name: 'server-schedules' }">
                        <font-awesome-icon class="mr-2" fixed-with icon="calendar-alt"/>
                        Schedules
                    </router-link>
                    <router-link :to="{ name: 'server-databases' }">
                        <font-awesome-icon class="mr-2" fixed-with icon="database"/>
                        Databases
                    </router-link>
                    <router-link :to="{ name: 'server-allocations' }">
                        <font-awesome-icon class="mr-2" fixed-with icon="globe"/>
                        Allocations
                    </router-link>
                    <router-link :to="{ name: 'server-settings' }">
                        <font-awesome-icon class="mr-2" fixed-with icon="cog"/>
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
    import ServerConsole from "./ServerConsole";
    import Navigation from '../core/Navigation';

    export default {
        components: {Navigation, ServerConsole}
    }
</script>
