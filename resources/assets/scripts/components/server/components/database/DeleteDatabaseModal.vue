<template>
    <div>
        <h2 class="font-medium text-neutral-900 mb-6">Delete this database?</h2>
        <p class="text-neutral-900 text-sm">This action <strong>cannot</strong> be undone. This will permanetly delete the <strong>{{database.name}}</strong> database and remove all associated data.</p>
        <div class="mt-6">
            <label class="input-label">Confirm database name</label>
            <input type="text" class="input" v-model="nameConfirmation"/>
        </div>
        <div class="mt-6 text-right">
            <button class="btn btn-sm btn-secondary mr-2" v-on:click="$emit('close')">Cancel</button>
            <button class="btn btn-sm btn-red" :disabled="disabled" v-on:click="deleteDatabase">
                <span class="spinner white" v-bind:class="{ hidden: !showSpinner }">&nbsp;</span>
                <span :class="{ hidden: showSpinner }">
                    Confirm Deletion
                </span>
            </button>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'delete-database-modal',
        props: {
            database: { type: Object, required: true },
        },

        data: function () {
            return {
                showSpinner: false,
                nameConfirmation: '',
            };
        },

        computed: {
            /**
             * Determine if the 'Delete' button should be enabled or not. This requires the user
             * to enter the database name before actually deleting the DB.
             */
            disabled: function () {
                return (
                    this.nameConfirmation !== this.database.name
                    && this.nameConfirmation !== this.database.name.split('_', 2)[1]
                );
            }
        },

        methods: {
            /**
             * Handle deleting the database for the server instance.
             */
            deleteDatabase: function () {
                this.nameConfirmation = '';
                this.showSpinner = true;

                window.axios.delete(this.route('api.client.servers.databases.delete', {
                    server: this.$route.params.id,
                    database: this.database.id,
                }))
                    .then(() => {
                        window.events.$emit('server:deleted-database', this.database.id);
                    })
                    .catch(err => {
                        this.clearFlashes();
                        console.error({ err });

                        const response = err.response;
                        if (response.data && typeof response.data.errors === 'object') {
                            response.data.errors.forEach((error) => {
                                this.error(error.detail);
                            });
                        }
                    })
                    .then(() => {
                        this.$emit('close');
                    })
            },
        }
    };
</script>

