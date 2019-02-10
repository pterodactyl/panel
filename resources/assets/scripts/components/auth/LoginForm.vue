<template>
    <form class="login-box" method="post"
          v-on:submit.prevent="submitForm"
    >
        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="input-open">
                <input class="input open-label" id="grid-username" type="text" name="user" aria-labelledby="grid-username-label" required
                       ref="email"
                       :class="{ 'has-content' : user.email.length > 0 }"
                       :readonly="showSpinner"
                       v-model="user.email"
                />
                <label id="grid-username-label" for="grid-username">{{ $t('strings.user_identifier') }}</label>
            </div>
        </div>
        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="input-open">
                <input class="input open-label" id="grid-password" type="password" name="password" aria-labelledby="grid-password-label" required
                       ref="password"
                       :class="{ 'has-content' : user.password && user.password.length > 0 }"
                       :readonly="showSpinner"
                       v-model="user.password"
                />
                <label id="grid-password-label" for="grid-password">{{ $t('strings.password') }}</label>
            </div>
        </div>
        <div>
            <button id="grid-login-button" class="btn btn-primary btn-jumbo" type="submit" aria-label="Log in"
                    v-bind:disabled="showSpinner">
                <span class="spinner white" v-bind:class="{ hidden: ! showSpinner }">&nbsp;</span>
                <span v-bind:class="{ hidden: showSpinner }">
                    {{ $t('auth.sign_in') }}
                </span>
            </button>
        </div>
        <div class="pt-6 text-center">
            <router-link class="text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600" aria-label="Forgot password"
                         :to="{ name: 'forgot-password' }">
                {{ $t('auth.forgot_password.label') }}
            </router-link>
        </div>
    </form>
</template>

<script lang="ts">
    import Vue from 'vue';
    import {isObject} from 'lodash';

    export default Vue.extend({
        name: 'LoginForm',

        data: function () {
            return {
                showSpinner: false,
                user: {
                    email: '',
                    password: '',
                }
            }
        },

        mounted: function () {
            (this.$refs.email as HTMLElement).focus();
        },

        methods: {
            // Handle a login request eminating from the form. If 2FA is required the
            // user will be presented with the 2FA modal window.
            submitForm: function () {
                this.showSpinner = true;

                this.$flash.clear();
                this.$store.dispatch('auth/login', {user: this.user.email, password: this.user.password})
                    .then(response => {
                        if (response.complete) {
                            return window.location = response.intended;
                        }

                        this.user.password = '';
                        this.showSpinner = false;
                        this.$router.push({name: 'checkpoint', query: {token: response.token}});
                    })
                    .catch(err => {
                        this.user.password = '';
                        this.showSpinner = false;
                        (this.$refs.password as HTMLElement).focus();
                        this.$store.commit('auth/logout');

                        if (!err.response) {
                            this.$flash.error('There was an error with the network request. Please try again.');
                            return console.error(err);
                        }

                        const response = err.response;
                        if (response.data && isObject(response.data.errors)) {
                            response.data.errors.forEach((error: any) => {
                                this.$flash.error(error.detail);
                            });
                        }
                    });
            },
        },
    });
</script>
