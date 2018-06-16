<template>
    <div class="nav">
        <div class="logo">
            <router-link :to="{ name: 'dashboard' }">
                Pterodactyl
            </router-link>
        </div>
        <div class="menu">
            <ul>
                <li>
                    <router-link :to="{ name: 'dashboard' }">
                        <server-icon aria-label="Server dashboard"/>
                    </router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'account' }">
                        <user-icon aria-label="Profile management"/>
                    </router-link>
                </li>
                <li>
                    <a :href="this.route('admin.index')">
                        <settings-icon aria-label="Administrative controls"/>
                    </a>
                </li>
                <li>
                    <a :href="this.route('auth.logout')" v-on:click.prevent="doLogout">
                        <log-out-icon aria-label="Sign out"/>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
    import { LogOutIcon, ServerIcon, SettingsIcon, UserIcon } from 'vue-feather-icons'

    export default {
        name: 'navigation',
        components: { LogOutIcon, ServerIcon, SettingsIcon, UserIcon },
        methods: {
            doLogout: function () {
                this.$store.commit('auth/logout');
                return window.location = this.route('auth.logout');
            },
        }
    };
</script>
