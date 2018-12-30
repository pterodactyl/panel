<template>
    <div id="configure-two-factor">
        <div class="h-16 text-center" v-show="spinner">
            <span class="spinner spinner-xl text-blue"></span>
        </div>
        <div id="container-disable-two-factor" v-if="response.enabled" v-show="!spinner">
            <h2 class="font-medium text-grey-darkest">{{ $t('dashboard.account.two_factor.disable.title') }}</h2>
            <div class="mt-6">
                <label class="input-label" for="grid-two-factor-token-disable">{{ $t('dashboard.account.two_factor.disable.field') }}</label>
                <input id="grid-two-factor-token-disable" type="number" class="input"
                       name="token"
                       v-model="token"
                       ref="token"
                       v-validate="'length:6'"
                       :class="{ error: errors.has('token') }"
                >
                <p class="input-help error" v-show="errors.has('token')">{{ errors.first('token') }}</p>
            </div>
            <div class="mt-6 w-full text-right">
                <button class="btn btn-sm btn-secondary mr-4" v-on:click="$emit('close')">
                    Cancel
                </button>
                <button class="btn btn-sm btn-red" type="submit"
                        :disabled="submitDisabled"
                        v-on:click.prevent="disableTwoFactor"
                >{{ $t('strings.disable') }}</button>
            </div>
        </div>
        <div id="container-enable-two-factor" v-else v-show="!spinner">
            <h2 class="font-medium text-grey-darkest">{{ $t('dashboard.account.two_factor.setup.title') }}</h2>
            <div class="flex mt-6">
                <div class="flex-none w-full sm:w-1/2 text-center">
                    <div class="h-48">
                        <img :src="response.qr_image" id="grid-qr-code" alt="Two-factor qr image" class="h-48">
                    </div>
                    <div>
                        <p class="text-xs text-grey-darker mb-2">{{ $t('dashboard.account.two_factor.setup.help') }}</p>
                        <p class="text-xs"><code>{{response.secret}}</code></p>
                    </div>
                </div>
                <div class="flex-none w-full sm:w-1/2">
                    <div>
                        <label class="input-label" for="grid-two-factor-token">{{ $t('dashboard.account.two_factor.setup.field') }}</label>
                        <input id="grid-two-factor-token" type="number" class="input"
                               name="token"
                               v-model="token"
                               ref="token"
                               v-validate="'length:6'"
                               :class="{ error: errors.has('token') }"
                        >
                        <p class="input-help error" v-show="errors.has('token')">{{ errors.first('token') }}</p>
                    </div>
                    <div class="mt-6">
                        <button class="btn btn-blue btn-jumbo" type="submit"
                                :disabled="submitDisabled"
                                v-on:click.prevent="enableTwoFactor"
                        >{{ $t('strings.enable') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import isObject from 'lodash/isObject';

    export default {
        name: 'TwoFactorAuthentication',
        data: function () {
            return {
                spinner: true,
                token: '',
                submitDisabled: true,
                response: {
                    enabled: false,
                    qr_image: '',
                    secret: '',
                },
            };
        },

        /**
         * Before the component is mounted setup the event listener. This event is fired when a user
         * presses the 'Configure 2-Factor' button on their account page. Once this happens we fire off
         * a HTTP request to get their information.
         */
        mounted: function () {
            window.events.$on('two_factor:open', () => {
                this.prepareModalContent();
            });
        },

        watch: {
            token: function (value) {
                this.$data.submitDisabled = value.length !== 6;
            },
        },

        methods: {
            /**
             * Determine the correct content to show in the modal.
             */
            prepareModalContent: function () {
                // Reset the data object when the modal is opened again.
                Object.assign(this.$data, this.$options.data());

                window.axios.get(this.route('account.two_factor'))
                    .finally(() => {
                        this.clearFlashes();
                    })
                    .then(response => {
                        this.$data.response = response.data;
                        this.$data.spinner = false;
                        Vue.nextTick().then(() => {
                            this.$refs.token.focus();
                        })
                    })
                    .catch(error => {
                        if (!error.response) {
                            this.error(error.message);
                        }

                        const response = error.response;
                        if (response.data && isObject(response.data.errors)) {
                            response.data.errors.forEach(e => {
                                this.error(e.detail);
                            });
                        }

                        this.$emit('close');
                    });
            },

            /**
             * Enable two-factor authentication on the account by validating the token provided by the user.
             * Close the modal once the request completes so that the success or error message can be shown
             * to the user.
             */
            enableTwoFactor: function () {
                return this._callInternalApi('account.two_factor.enable', 'enabled');
            },

            /**
             * Disables two-factor authentication for the client account and closes the modal.
             */
            disableTwoFactor: function () {
                return this._callInternalApi('account.two_factor.disable', 'disabled');
            },

            /**
             * Call the Panel API endpoint and handle errors.
             *
             * @param {String} route
             * @param {String} langKey
             * @private
             */
            _callInternalApi: function (route, langKey) {
                window.axios.post(this.route(route), {
                    token: this.$data.token,
                })
                    .finally(() => {
                        this.clearFlashes();
                    })
                    .then(response => {
                        if (response.data.success) {
                            this.success(this.$t(`dashboard.account.two_factor.${langKey}`));
                        } else {
                            this.error(this.$t('dashboard.account.two_factor.invalid'));
                        }
                    })
                    .catch(error => {
                        if (!error.response) {
                            this.error(error.message);
                        }

                        const response = error.response;
                        if (response.data && isObject(response.data.errors)) {
                            response.data.errors.forEach(e => {
                                this.error(e.detail);
                            });
                        }
                    })
                    .finally(() => {
                        this.$emit('close');
                    })
            }
        },
    };
</script>
