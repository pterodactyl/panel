import Vue from 'vue';
import { debounce, isObject } from 'lodash';
import { mapState } from 'vuex';
import {AxiosError} from "axios";

export default Vue.component('navigation', {
    data: function () {
        return {
            loadingResults: false,
            searchActive: false,
        };
    },

    computed: {
        ...mapState('dashboard', ['servers']),
        searchTerm: {
            get: function (): string {
                return this.$store.getters['dashboard/getSearchTerm'];
            },
            set: function (value: string): void {
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
        search: debounce(function (this: any): void {
            if (this.searchTerm.length >= 3) {
                this.loadingResults = true;
                this.gatherSearchResults();
            }
        }, 500),

        gatherSearchResults: function (): void {
            this.$store.dispatch('dashboard/loadServers')
                .catch((err: AxiosError) => {
                    console.error(err);

                    const response = err.response;
                    if (response && isObject(response.data.errors)) {
                        response.data.errors.forEach((error: any) => {
                            this.$flash.error(error.detail);
                        });
                    }
                })
                .then(() => {
                    this.loadingResults = false;
                });
        },

        doLogout: function () {
            this.$store.commit('auth/logout');
            window.location.assign(this.route('auth.logout'));
        },

        documentClick: function (e: Event) {
            if (this.$refs.searchContainer) {
                if (this.$refs.searchContainer !== e.target && !(this.$refs.searchContainer as HTMLElement).contains(e.target as HTMLElement)) {
                    this.searchActive = false;
                }
            }
        },
    },

    template: `
        <div class="nav flex flex-grow">
            <div class="flex flex-1 justify-center items-center container">
                <div class="logo">
                    <router-link :to="{ name: 'dashboard' }">
                        Pterodactyl
                    </router-link>
                </div>
                <div class="menu flex-1">
                    <router-link :to="{ name: 'dashboard' }">
                        <icon name="server" aria-label="Server dashboard" class="h-4 self-center"/>
                    </router-link>
                    <router-link :to="{ name: 'account' }">
                        <icon name="user" aria-label="Profile management" class="h-4"/>
                    </router-link>
                    <a :href="this.route('admin.index')">
                        <icon name="settings" aria-label="Administrative controls" class="h-4"/>
                    </a>
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
                                        <span class="text-sm text-neutral-800">Loading...</span>
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
                                        <span class="font-bold text-neutral-900">{{ server.name }}</span><br />
                                        <span class="font-light text-neutral-600 text-sm" v-if="server.description.length > 0">{{ server.description }}</span>
                                    </div>
                                    <div class="flex-none">
                                        <span class="pillbox bg-indigo">{{ server.node }}</span>
                                    </div>
                                </div>
                            </router-link>
                        </div>
                    </div>
                </div>
                <div class="menu">
                    <a :href="this.route('auth.logout')" v-on:click.prevent="doLogout">
                        <icon name="log-out" aria-label="Sign out" class="h-4"/>
                    </a>
                </div>
            </div>
        </div>
    `
})
