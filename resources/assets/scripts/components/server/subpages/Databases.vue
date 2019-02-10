<template>
    <div>
        <div v-if="loading">
            <div class="spinner spinner-xl blue"></div>
        </div>
        <div class="animate fadein" v-else>
            <div class="content-box mb-6" v-if="!databases.length">
                <div class="flex items-center">
                    <Icon name="database" class="flex-none text-neutral-800"></icon>
                    <div class="flex-1 px-4 text-neutral-800">
                        <p>You have no databases.</p>
                    </div>
                </div>
            </div>
            <div v-else>
                <DatabaseRow v-for="database in databases" :database="database" :key="database.name"/>
            </div>
            <div>
                <button class="btn btn-primary btn-lg" v-on:click="showCreateModal = true">Create new database</button>
            </div>
            <Modal :show="showCreateModal" v-on:close="showCreateModal = false">
                <CreateDatabaseModal
                        v-on:close="showCreateModal = false"
                        v-on:database="handleModalCallback"
                        v-if="showCreateModal"
                />
            </modal>
        </div>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import {filter, map} from 'lodash';
    import Modal from '@/components/core/Modal.vue';
    import CreateDatabaseModal from './../components/database/CreateDatabaseModal.vue';
    import Icon from "@/components/core/Icon.vue";
    import {ServerDatabase} from "@/api/server/types";
    import DatabaseRow from "@/components/server/components/database/DatabaseRow.vue";

    type DataStructure = {
        loading: boolean,
        showCreateModal: boolean,
        databases: Array<ServerDatabase>,
    }

    export default Vue.extend({
        name: 'ServerDatabases',
        components: {DatabaseRow, CreateDatabaseModal, Modal, Icon},

        data: function (): DataStructure {
            return {
                databases: [],
                loading: true,
                showCreateModal: false,
            };
        },

        mounted: function () {
            this.getDatabases();

            window.events.$on('server:deleted-database', this.removeDatabase);
        },

        methods: {
            /**
             * Get all of the databases that exist for this server.
             */
            getDatabases: function () {
                this.$flash.clear();
                this.loading = true;

                window.axios.get(this.route('api.client.servers.databases', {
                    server: this.$route.params.id,
                    include: 'password'
                }))
                    .then(response => {
                        this.databases = map(response.data.data, (object) => {
                            const data = object.attributes;

                            data.password = data.relationships.password.attributes.password;
                            data.showPassword = false;
                            delete data.relationships;

                            return data;
                        });
                    })
                    .catch(err => {
                        this.$flash.error('There was an error encountered while attempting to fetch databases for this server.');
                        console.error(err);
                    })
                    .then(() => {
                        this.loading = false;
                    });
            },

            /**
             * Add the database to the list of existing databases automatically when the modal
             * is closed with a successful callback.
             */
            handleModalCallback: function (data: ServerDatabase) {
                this.databases.push(data);
            },

            /**
             * Handle event that is removing a database.
             */
            removeDatabase: function (databaseId: string) {
                this.databases = filter(this.databases, (database) => {
                    return database.id !== databaseId;
                });
            }
        },
    });
</script>
