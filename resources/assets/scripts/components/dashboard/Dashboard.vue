<template>
    <div>
        <Navigation/>
        <div class="container">
            <Flash container="mt-4"/>
            <div class="server-search animate fadein">
                <input type="text"
                       :placeholder="$t('dashboard.index.search')"
                       @input="onChange"
                       v-model="searchTerm"
                       ref="search"
                />
            </div>
            <div v-if="this.loading" class="my-4 animate fadein">
                <div class="text-center h-16 my-20">
                    <span class="spinner spinner-xl spinner-thick blue"></span>
                </div>
            </div>
            <TransitionGroup class="flex flex-wrap justify-center sm:justify-start" tag="div" v-else>
                <ServerBox
                        v-for="(server, index) in servers"
                        :key="index"
                        :server="server"
                />
            </TransitionGroup>
        </div>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import {debounce, isObject} from 'lodash';
    import {mapState} from 'vuex';
    import Flash from "./../Flash.vue";
    import Navigation from "./../core/Navigation.vue";
    import {AxiosError} from "axios";
    import ServerBox from "./ServerBox.vue";

    type DataStructure = {
        backgroundedAt: Date,
        documentVisible: boolean,
        loading: boolean,
        servers?: Array<any>,
        searchTerm?: string,
    }

    export default Vue.extend({
        name: 'Dashboard',
        components: {
            ServerBox,
            Navigation,
            Flash
        },

        data: function (): DataStructure {
            return {
                backgroundedAt: new Date(),
                documentVisible: true,
                loading: false,
            }
        },

        /**
         * Start loading the servers before the DOM $.el is created. If we already have servers
         * stored in vuex shows those and don't fire another API call just to load them again.
         */
        created: function () {
            if (!this.servers || this.servers.length === 0) {
                this.loadServers();
            }
        },

        /**
         * Once the page is mounted set a function to run every 10 seconds that will
         * iterate through the visible servers and fetch their resource usage.
         */
        mounted: function () {
            (this.$refs.search as HTMLElement).focus();
        },

        computed: {
            ...mapState('dashboard', ['servers']),
            searchTerm: {
                get: function (): string {
                    return this.$store.getters['dashboard/getSearchTerm'];
                },
                set: function (value: string): void {
                    this.$store.dispatch('dashboard/setSearchTerm', value);
                },
            },
        },

        methods: {
            /**
             * Load the user's servers and render them onto the dashboard.
             */
            loadServers: function () {
                this.loading = true;
                this.$flash.clear();

                this.$store.dispatch('dashboard/loadServers')
                    .then(() => {
                        if (!this.servers || this.servers.length === 0) {
                            this.$flash.info(this.$t('dashboard.index.no_matches'));
                        }
                    })
                    .catch((err: AxiosError) => {
                        console.error(err);
                        const response = err.response;
                        if (response && isObject(response.data.errors)) {
                            response.data.errors.forEach((error: any) => {
                                this.$flash.error(error.detail);
                            });
                        }
                    })
                    .then(() => this.loading = false);
            },

            /**
             * Handle a search for servers but only call the search function every 500ms
             * at the fastest.
             */
            onChange: debounce(function (this: any): void {
                this.loadServers();
            }, 500),
        },
    });
</script>
