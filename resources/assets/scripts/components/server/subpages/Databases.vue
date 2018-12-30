<template>
    <div>
        <div v-if="loading">
            <div class="spinner spinner-xl blue"></div>
        </div>
        <div class="animate fadein" v-else>
            <div class="content-box mb-6" v-if="!databases.length">
                <div class="flex items-center">
                    <database-icon class="flex-none text-grey-darker"></database-icon>
                    <div class="flex-1 px-4 text-grey-darker">
                        <p>You have no databases.</p>
                    </div>
                </div>
            </div>
            <div v-else>
                <database-row v-for="database in databases" :database="database" :key="database.name"/>
            </div>
            <div>
                <button class="btn btn-blue btn-lg" v-on:click="showCreateModal = true">Create new database</button>
            </div>
            <modal :show="showCreateModal" v-on:close="showCreateModal = false">
                <create-database-modal
                        v-on:close="showCreateModal = false"
                        v-on:database="handleModalCallback"
                        v-if="showCreateModal"
                />
            </modal>
        </div>
    </div>
</template>

<script lang="ts">
    import { DatabaseIcon, LockIcon, Trash2Icon } from 'vue-feather-icons';
    import map from 'lodash/map';
    import filter from 'lodash/filter';
    import Modal from '../../core/Modal';
    import CreateDatabaseModal from '../components/database/CreateDatabaseModal';
    import DatabaseRow from '../components/database/DatabaseRow';

    export default {
        name: 'databases-page',
        components: {DatabaseRow, CreateDatabaseModal, Modal, DatabaseIcon, LockIcon, Trash2Icon },

        data: function () {
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
                this.clearFlashes();
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
                        this.error('There was an error encountered while attempting to fetch databases for this server.');
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
            handleModalCallback: function (object) {
                const data = object;
                data.password = data.relationships.password.attributes.password;
                data.showPassword = false;

                delete data.relationships;

                this.databases.push(data);
            },

            /**
             * Handle event that is removing a database.
             *
             * @param databaseId
             */
            removeDatabase: function (databaseId) {
                this.databases = filter(this.databases, (database) => {
                    return database.id !== databaseId;
                });
            }
        }
    };
</script>
