<template>
    <div>
        <message-box class="alert error mb-6" :message="errorMessage" v-show="errorMessage.length"/>
        <h2 class="font-medium text-grey-darkest mb-6">Create a new database</h2>
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
            <button class="btn btn-green btn-sm"
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

<script>
    import MessageBox from '../../../MessageBox';
    import get from 'lodash/get';

    export default {
        name: 'create-database-modal',
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

                window.axios.post(this.route('api.client.servers.databases', {
                    server: this.$route.params.id,
                }), {
                    database: this.database,
                    remote: this.remote,
                }).then(response => {
                    this.$emit('database', response.data.attributes);
                    this.$emit('close');
                }).catch(err => {
                    if (get(err, 'response.data.errors[0]')) {
                        this.errorMessage = err.response.data.errors[0].detail;
                    }

                    console.error('A network error was encountered while processing this request.', err.response);
                }).then(() => {
                    this.loading = false;
                    this.showSpinner = false;
                })
            }
        }
    };
</script>

