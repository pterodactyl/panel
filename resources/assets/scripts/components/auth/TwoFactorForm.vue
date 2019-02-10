<template>
    <form class="login-box" method="post"
          v-on:submit.prevent="submitToken"
    >
        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="input-open">
                <input class="input open-label" id="grid-code" type="number" name="token" aria-labelledby="grid-username" required
                       ref="code"
                       :class="{ 'has-content' : code.length > 0 }"
                       v-model="code"
                />
                <label for="grid-code">{{ $t('auth.two_factor.label') }}</label>
                <p class="text-neutral-800 text-xs">{{ $t('auth.two_factor.label_help') }}</p>
            </div>
        </div>
        <div>
            <button class="btn btn-primary btn-jumbo" type="submit">
                {{ $t('auth.sign_in') }}
            </button>
        </div>
        <div class="pt-6 text-center">
            <router-link class="text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600"
                         :to="{ name: 'login' }"
            >
                Back to Login
            </router-link>
        </div>
    </form>
</template>

<script lang="ts">
    import Vue from 'vue';
    import {AxiosError, AxiosResponse} from "axios";
    import {isObject} from 'lodash';

    export default Vue.extend({
        name: 'TwoFactorForm',

        data: function () {
            return {
                code: '',
            };
        },

        mounted: function () {
            if ((this.$route.query.token || '').length < 1) {
                return this.$router.push({name: 'login'});
            }

            (this.$refs.code as HTMLElement).focus();
        },

        methods: {
            submitToken: function () {
                this.$flash.clear();
                window.axios.post(this.route('auth.login-checkpoint'), {
                    confirmation_token: this.$route.query.token,
                    authentication_code: this.$data.code,
                })
                    .then((response: AxiosResponse) => {
                        if (!(response.data instanceof Object)) {
                            throw new Error('An error was encountered while processing this login.');
                        }

                        localStorage.setItem('token', response.data.token);
                        this.$store.dispatch('login');

                        window.location = response.data.intended;
                    })
                    .catch((err: AxiosError) => {
                        this.$store.dispatch('logout');
                        if (!err.response) {
                            return console.error(err);
                        }

                        const response = err.response;
                        if (response.data && isObject(response.data.errors)) {
                            response.data.errors.forEach((error: any) => {
                                this.$flash.error(error.detail);
                            });
                            this.$router.push({name: 'login'});
                        }
                    });
            }
        },
    });
</script>
