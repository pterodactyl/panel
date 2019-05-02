<template>
    <div>
        <MessageBox class="alert error mb-6" :message="errorMessage" v-show="errorMessage.length"/>
        <h2 class="font-medium text-neutral-900 mb-6">Create a new database</h2>
        <div class="mb-6">
            <label class="input-label" for="grid-database-name">Database name</label>
            <input id="grid-database-name" type="text" class="input" name="database_name" required
                   v-model="database"
                   v-validate="{ alpha_dash: true, max: 100 }"
                   :class="{ error: errors.has('database_name') }"
            >
            <p class="input-help error" v-show="errors.has('database_name')">{{ errors.first('database_name') }}</p>
        </div>
        <div class="mb-6">
            <label class="input-label" for="grid-database-remote">Allow connections from</label>
            <input id="grid-database-remote" type="text" class="input" name="remote" required
                   v-model="remote"
                   v-validate="{ regex: /^[0-9%.]{1,15}$/ }"
                   :class="{ error: errors.has('remote') }"
            >
            <p class="input-help error" v-show="errors.has('remote')">{{ errors.first('remote') }}</p>
        </div>
        <div class="text-right">
            <button class="btn btn-secondary btn-sm mr-2" v-on:click.once="$emit('close')">Cancel</button>
            <button class="btn btn-primary btn-sm"
                    :disabled="errors.any() || !canSubmit || showSpinner"
                    v-on:click="submit"
            >
                <span class="spinner white" v-bind:class="{ hidden: !showSpinner }">&nbsp;</span>
                <span :class="{ hidden: showSpinner }">
                        Create
                    </span>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import MessageBox from "@/components/MessageBox.vue";
    import {createDatabase} from "@/api/server/createDatabase";

    export default Vue.extend({
        name: 'CreateDatabaseModal',
        components: {MessageBox},

        data: function () {
            return {
                loading: false,
                showSpinner: false,
                database: '',
                remote: '%',
                errorMessage: '',
            };
        },

        computed: {
            canSubmit: function () {
                return this.database.length && this.remote.length;
            },
        },

        methods: {
            submit: function () {
                this.showSpinner = true;
                this.errorMessage = '';
                this.loading = true;

                createDatabase(this.$route.params.id, this.database, this.remote)
                    .then((response) => {
                        this.$emit('database', response);
                        this.$emit('close');
                    })
                    .catch((err: Error | string): void => {
                        if (typeof err === 'string') {
                            this.errorMessage = err;
                            return;
                        }

                        console.error('A network error was encountered while processing this request.', {err});
                    })
                    .then(() => {
                        this.loading = false;
                        this.showSpinner = false;
                    });
            }
        },
    });
</script>
