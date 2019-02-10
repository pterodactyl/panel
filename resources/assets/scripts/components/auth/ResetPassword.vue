<template>
    <form class="bg-white shadow-lg rounded-lg pt-10 px-8 pb-6 mb-4 animate fadein" method="post"
          v-on:submit.prevent="submitForm"
    >
        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="input-open">
                <input class="input open-label" id="grid-email" type="email" aria-labelledby="grid-email" required
                       ref="email"
                       :class="{ 'has-content': email.length > 0 }"
                       :readonly="showSpinner"
                       v-on:input="updateEmailField"
                />
                <label for="grid-email">{{ $t('strings.email') }}</label>
            </div>
        </div>
        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="input-open">
                <input class="input open-label" id="grid-password" type="password" aria-labelledby="grid-password" required
                       ref="password"
                       :class="{ 'has-content' : password.length > 0 }"
                       :readonly="showSpinner"
                       v-model="password"
                />
                <label for="grid-password">{{ $t('strings.password') }}</label>
                <p class="text-neutral-800 text-xs">{{ $t('auth.password_requirements') }}</p>
            </div>
        </div>
        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="input-open">
                <input class="input open-label" id="grid-password-confirmation" type="password" aria-labelledby="grid-password-confirmation" required
                       :class="{ 'has-content' : passwordConfirmation.length > 0 }"
                       :readonly="showSpinner"
                       v-model="passwordConfirmation"
                />
                <label for="grid-password-confirmation">{{ $t('strings.confirm_password') }}</label>
            </div>
        </div>
        <div>
            <button class="btn btn-primary btn-jumbo" type="submit" v-bind:class="{ disabled: showSpinner }">
                <span class="spinner white" v-bind:class="{ hidden: ! showSpinner }">&nbsp;</span>
                <span v-bind:class="{ hidden: showSpinner }">
                        {{ $t('auth.reset_password.button') }}
                    </span>
            </button>
        </div>
        <div class="pt-6 text-center">
            <router-link class="text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600"
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

    export default Vue.component('reset-password', {
        props: {
            token: {type: String, required: true},
            email: {type: String, required: false},
        },

        mounted: function () {
            if (this.$props.email.length > 0) {
                (this.$refs.email as HTMLElement).setAttribute('value', this.$props.email);
                (this.$refs.password as HTMLElement).focus();
            }
        },

        data: function () {
            return {
                errors: [],
                showSpinner: false,
                password: '',
                passwordConfirmation: '',
                submitDisabled: true,
            };
        },

        methods: {
            updateEmailField: function (event: { target: HTMLInputElement }) {
                this.submitDisabled = event.target.value.length === 0;
            },

            submitForm: function () {
                this.showSpinner = true;

                this.$flash.clear();
                window.axios.post(this.route('auth.reset-password'), {
                    email: this.$props.email,
                    password: this.password,
                    password_confirmation: this.passwordConfirmation,
                    token: this.$props.token,
                })
                    .then((response: AxiosResponse) => {
                        if (!(response.data instanceof Object)) {
                            throw new Error('An error was encountered while processing this login.');
                        }

                        if (response.data.send_to_login) {
                            this.$flash.success('Your password has been reset, please login to continue.');
                            return this.$router.push({name: 'login'});
                        }

                        return window.location = response.data.redirect_to;
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
                            (this.$refs.password as HTMLElement).focus();
                        }
                    });
            }
        },
    });
</script>
