<template>
    <div>
        <div v-if="loading">
            <div class="spinner spinner-xl blue"></div>
        </div>
        <div class="context-box" v-else-if="!databases.length">
            <div class="flex items-center">
                <database-icon class="flex-none text-grey-darker"></database-icon>
                <div class="flex-1 px-4 text-grey-darker">
                    <p>You have no databases.</p>
                </div>
            </div>
        </div>
        <div v-else>
            <div class="content-box mb-6" v-for="database in databases" :key="database.name">
                <div class="flex items-center text-grey-darker">
                    <database-icon class="flex-none text-green"></database-icon>
                    <div class="flex-1 px-4">
                        <p class="uppercase text-xs text-grey pb-1 select-none">Database Name</p>
                        <p>{{database.name}}</p>
                    </div>
                    <div class="flex-1 px-4">
                        <p class="uppercase text-xs text-grey pb-1 select-none">Username</p>
                        <p>{{database.username}}</p>
                    </div>
                    <div class="flex-1 px-4">
                        <p class="uppercase text-xs text-grey pb-1 select-none">Password</p>
                        <p>
                            <code class="text-sm cursor-pointer" v-on:click="revealPassword(database)">
                                <span class="select-none" v-if="!database.showPassword">
                                    <lock-icon class="h-3"/> &bull;&bull;&bull;&bull;&bull;&bull;
                                </span>
                                <span v-else>{{database.password}}</span>
                            </code>
                        </p>
                    </div>
                    <div class="flex-1 px-4">
                        <p class="uppercase text-xs text-grey pb-1 select-none">Server</p>
                        <p><code class="text-sm">{{database.host.address}}:{{database.host.port}}</code></p>
                    </div>
                </div>
            </div>
            <div>
                <button class="btn btn-blue btn-lg" v-on:click="showCreateModal = true">Create new database</button>
            </div>
        </div>
        <modal :show="showCreateModal" v-on:close="showCreateModal = false">
            <create-database-modal
                    v-on:close="showCreateModal = false"
                    v-on:database="handleModalCallback"
                    v-if="showCreateModal"
            />
        </modal>
    </div>
</template>

<script>
    import { DatabaseIcon, LockIcon } from 'vue-feather-icons';
    import map from 'lodash/map';
    import Modal from '../../core/Modal';
    import CreateDatabaseModal from '../components/CreateDatabaseModal';

    export default {
        name: 'databases-page',
        components: {CreateDatabaseModal, Modal, DatabaseIcon, LockIcon },

        data: function () {
            return {
                databases: [],
                loading: true,
                showCreateModal: false,
            };
        },

        mounted: function () {
            this.getDatabases();
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
                console.log('handle', object);

                const data = object;
                data.password = data.relationships.password.attributes.password;
                data.showPassword = false;

                delete data.relationships;

                this.databases.push(data);
            },

            /**
             * Show the password for a given database object.
             *
             * @param {Object} database
             */
            revealPassword: function (database) {
                this.databases.forEach((d) => {
                    d.showPassword = d === database ? d.showPassword : false;
                });

                database.showPassword = !database.showPassword;
            },
        }
    };
</script>
