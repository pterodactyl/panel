<template>
    <div class="nav flex">
        <div class="logo flex-1">
            <router-link :to="{ name: 'dashboard' }">
                Pterodactyl
            </router-link>
        </div>
        <div class="search-box flex-none" v-if="$route.name !== 'dashboard'">
            <input type="text" class="search-input" id="searchInput" placeholder="Search..."
                   :class="{ 'has-search-results': servers.length > 0 && searchActive }"
                   v-on:focus="searchActive = true"
                   v-on:blur="searchActive = false"
                   v-on:input="search"
                   v-model="searchTerm"
            />
            <div class="search-results select-none" :class="{ 'hidden': servers.length === 0 || !searchActive }">
                <router-link
                        v-for="server in servers"
                        :key="server.identifier"
                        :to="{ name: 'server', params: { id: server.identifier } }"
                >
                    <div class="flex items-center">
                        <div class="flex-1">
                            <span class="font-bold text-grey-darkest">{{ server.name }}</span><br />
                            <span class="font-light text-grey-dark text-sm" v-if="server.description.length > 0">{{ server.description }}</span>
                        </div>
                        <div class="flex-none">
                            <span class="pillbox bg-indigo">{{ server.node }}</span>
                        </div>
                    </div>
                </router-link>
            </div>
        </div>
        <div class="menu flex-none">
            <ul>
                <li>
                    <router-link :to="{ name: 'dashboard' }">
                        <server-icon aria-label="Server dashboard" class="h-4"/>
                    </router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'account' }">
                        <user-icon aria-label="Profile management" class="h-4"/>
                    </router-link>
                </li>
                <li>
                    <a :href="this.route('admin.index')">
                        <settings-icon aria-label="Administrative controls" class="h-4"/>
                    </a>
                </li>
                <li>
                    <a :href="this.route('auth.logout')" v-on:click.prevent="doLogout">
                        <log-out-icon aria-label="Sign out" class="h-4"/>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
    import debounce from 'lodash/debounce';
    import { mapState } from 'vuex';
    import { LogOutIcon, ServerIcon, SettingsIcon, UserIcon } from 'vue-feather-icons'

    export default {
        name: 'navigation',
        components: { LogOutIcon, ServerIcon, SettingsIcon, UserIcon },

        data: function () {
            return {
                searchActive: false,
            };
        },

        computed: {
            ...mapState('dashboard', ['servers']),
            searchTerm: {
                get: function () {
                    return this.$store.getters['dashboard/getSearchTerm'];
                },
                set: function (value) {
                    this.$store.dispatch('dashboard/setSearchTerm', value);
                }
            }
        },

        methods: {
            search: debounce(function () {
                if (this.searchTerm.length > 3) {
                    this.gatherSearchResults(this.searchTerm);
                }
            }, 500),

            gatherSearchResults: function () {
                this.$store.dispatch('dashboard/loadServers')
                    .then(() => {
                        if (this.servers.length === 0) {
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        const response = err.response;
                        if (response.data && isObject(response.data.errors)) {
                            response.data.errors.forEach(error => {
                                this.error(error.detail);
                            });
                        }
                    });
            },

            doLogout: function () {
                this.$store.commit('auth/logout');
                return window.location = this.route('auth.logout');
            },
        }
    };
</script>
