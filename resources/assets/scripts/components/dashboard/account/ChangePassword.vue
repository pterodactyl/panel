<template>
    <div id="change-password-container" :class>
        <form method="post" v-on:submit.prevent="submitForm">
            <div class="content-box">
                <h2 class="mb-6 text-neutral-900 font-medium">{{ $t('dashboard.account.password.title') }}</h2>
                <div class="mt-6">
                    <label for="grid-password-current" class="input-label">{{ $t('strings.password') }}</label>
                    <input id="grid-password-current" name="current_password" type="password" class="input" required
                           ref="current"
                           v-model="current"
                    >
                </div>
                <div class="mt-6">
                    <label for="grid-password-new" class="input-label">{{ $t('strings.new_password') }}</label>
                    <input id="grid-password-new" name="password" type="password" class="input" required
                           :class="{ error: errors.has('password') }"
                           v-model="newPassword"
                           v-validate="'min:8'"
                    >
                    <p class="input-help error" v-show="errors.has('password')">{{ errors.first('password') }}</p>
                    <p class="input-help">{{ $t('dashboard.account.password.requirements') }}</p>
                </div>
                <div class="mt-6">
                    <label for="grid-password-new-confirm" class="input-label">{{ $t('strings.confirm_password') }}</label>
                    <input id="grid-password-new-confirm" name="password_confirmation" type="password" class="input" required
                           :class="{ error: errors.has('password_confirmation') }"
                           v-model="confirmNew"
                           v-validate="{is: newPassword}"
                           data-vv-as="password"
                    >
                    <p class="input-help error" v-show="errors.has('password_confirmation')">{{ errors.first('password_confirmation') }}</p>
                </div>
                <div class="mt-6 text-right">
                    <button class="btn btn-primary btn-sm text-right" type="submit">{{ $t('strings.save') }}</button>
                </div>
            </div>
        </form>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import {isObject} from 'lodash';
    import {AxiosError} from "axios";

    export default Vue.extend({
        name: 'ChangePassword',
        data: function () {
            return {
                current: '',
                newPassword: '',
                confirmNew: '',
            };
        },

        methods: {
            submitForm: function () {
                this.$flash.clear();
                this.$validator.pause();

                window.axios.put(this.route('api.client.account.update-password'), {
                    current_password: this.current,
                    password: this.newPassword,
                    password_confirmation: this.confirmNew,
                })
                    .then(() => this.current = '')
                    .then(() => {
                        this.newPassword = '';
                        this.confirmNew = '';

                        this.$flash.success(this.$t('dashboard.account.password.updated'));
                    })
                    .catch((err: AxiosError) => {
                        if (!err.response) {
                            this.$flash.error('There was an error with the network request. Please try again.');
                            console.error(err);
                            return;
                        }

                        const response = err.response;
                        if (response.data && isObject(response.data.errors)) {
                            response.data.errors.forEach((error: any) => {
                                this.$flash.error(error.detail);
                            });
                        }
                    })
                    .then(() => {
                        this.$validator.resume();
                        (this.$refs.current as HTMLElement).focus();
                    })
            }
        },
    });
</script>
