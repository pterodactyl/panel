<template>
    <div class="nav flex">
        <div class="logo flex-1">
            <router-link :to="{ name: 'dashboard' }">
                Pterodactyl
            </router-link>
        </div>
        <div class="search-box flex-none" v-if="$route.name !== 'dashboard'" ref="searchContainer">
            <input type="text" class="search-input" id="searchInput" placeholder="Search..."
                   :class="{ 'has-search-results': ((servers.length > 0 && searchTerm.length >= 3) || loadingResults) && searchActive }"
                   v-on:focus="searchActive = true"
                   v-on:input="search"
                   v-model="searchTerm"
            />
            <div class="search-results select-none" :class="{ 'hidden': (servers.length === 0 && !loadingResults) || !searchActive || searchTerm.length < 3 }">
                <div v-if="loadingResults">
                    <a href="#">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <span class="text-sm text-grey-darker">Loading...</span>
                            </div>
                            <div class="flex-none">
                                <span class="spinner spinner-relative"></span>
                            </div>
                        </div>
                    </a>
                </div>
                <div v-else v-for="server in servers" :key="server.identifier">
                    <router-link :to="{ name: 'server', params: { id: server.identifier }}" v-on:click.native="searchActive = false">
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

<script lang="ts">
    import debounce from 'lodash/debounce';
    import { mapState } from 'vuex';
    import { LogOutIcon, ServerIcon, SettingsIcon, UserIcon } from 'vue-feather-icons'

    export default {
        name: 'navigation',
        components: { LogOutIcon, ServerIcon, SettingsIcon, UserIcon },

        data: function () {
            return {
                loadingResults: false,
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

        created: function () {
            document.addEventListener('click', this.documentClick);
        },

        beforeDestroy: function () {
            document.removeEventListener('click', this.documentClick);
        },

        methods: {
            search: debounce(function () {
                if (this.searchTerm.length >= 3) {
                    this.loadingResults = true;
                    this.gatherSearchResults(this.searchTerm);
                }
            }, 500),

            gatherSearchResults: function () {
                this.$store.dispatch('dashboard/loadServers')
                    .catch(err => {
                        console.error(err);
                        const response = err.response;
                        if (response.data && isObject(response.data.errors)) {
                            response.data.errors.forEach(error => {
                                this.error(error.detail);
                            });
                        }
                    })
                    .then(() => {
                        this.loadingResults = false;
                    });
            },

            doLogout: function () {
                this.$store.commit('auth/logout');
                return window.location = this.route('auth.logout');
            },

            documentClick: function (e) {
                if (this.$refs.searchContainer) {
                    if (this.$refs.searchContainer !== e.target && !this.$refs.searchContainer.contains(e.target)) {
                        this.searchActive = false;
                    }
                }
            },
        }
    };
</script>
