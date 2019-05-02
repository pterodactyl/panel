<template>
    <form class="login-box" method="post" v-on:submit.prevent="submitForm">
        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="input-open">
                <input class="input open-label" id="grid-email" type="email" aria-labelledby="grid-email-label" required
                       ref="email"
                       v-bind:class="{ 'has-content': email.length > 0 }"
                       v-bind:readonly="showSpinner"
                       v-bind:value="email"
                       v-on:input="updateEmail($event)"
                />
                <label for="grid-email" id="grid-email-label">{{ $t('strings.email') }}</label>
                <p class="text-neutral-800 text-xs">{{ $t('auth.forgot_password.label_help') }}</p>
            </div>
        </div>
        <div>
            <button class="btn btn-primary btn-jumbo" type="submit" v-bind:disabled="submitDisabled">
                <span class="spinner white" v-bind:class="{ hidden: ! showSpinner }">&nbsp;</span>
                <span v-bind:class="{ hidden: showSpinner }">
                        {{ $t('auth.forgot_password.button') }}
                    </span>
            </button>
        </div>
        <div class="pt-6 text-center">
            <router-link class="text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600"
                         aria-label="Go to login"
                         :to="{ name: 'login' }"
            >
                {{ $t('auth.go_to_login') }}
            </router-link>
        </div>
    </form>
</template>

<script lang="ts">
    import Vue from 'vue';
    import {isObject} from 'lodash';
    import {AxiosError, AxiosResponse} from "axios";

    export default Vue.extend({
        name: 'ForgotPassword',

        mounted: function () {
            if (this.$refs.email) {
                (this.$refs.email as HTMLElement).focus();
            }
        },

        data: function () {
            return {
                X_CSRF_TOKEN: window.X_CSRF_TOKEN,
                submitDisabled: false,
                showSpinner: false,
                email: '',
            };
        },

        methods: {
            updateEmail: function (event: { target: HTMLInputElement }) {
                this.submitDisabled = false;
                this.$emit('update-email', event.target.value);
            },

            submitForm: function () {
                this.submitDisabled = true;
                this.showSpinner = true;
                this.$flash.clear();

                window.axios.post(this.route('auth.forgot-password'), {
                    email: this.email,
                })
                    .then((response: AxiosResponse) => {
                        if (!(response.data instanceof Object)) {
                            throw new Error('An error was encountered while processing this request.');
                        }

                        this.submitDisabled = false;
                        this.showSpinner = false;
                        this.$flash.success(response.data.status);
                        this.$router.push({name: 'login'});
                    })
                    .catch((err: AxiosError) => {
                        this.showSpinner = false;
                        if (!err.response) {
                            return console.error(err);
                        }

                        const response = err.response;
                        if (response.data && isObject(response.data.errors)) {
                            response.data.errors.forEach((error: any) => {
                                this.$flash.error(error.detail);
                            });
                        }
                    });
            }
        },
    });
</script>
