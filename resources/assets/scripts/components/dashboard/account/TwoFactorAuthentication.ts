import Vue from 'vue';
import {isObject} from 'lodash';
import {AxiosError, AxiosResponse} from "axios";

export default Vue.component('two-factor-authentication', {
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
            this.submitDisabled = value.length !== 6;
        },
    },

    methods: {
        /**
         * Determine the correct content to show in the modal.
         */
        prepareModalContent: function () {
            // Reset the data object when the modal is opened again.
            // @ts-ignore
            Object.assign(this.$data, this.$options.data());

            this.$flash.clear();
            window.axios.get(this.route('account.two_factor'))
                .then((response: AxiosResponse) => {
                    this.response = response.data;
                    this.spinner = false;
                    Vue.nextTick().then(() => {
                        (this.$refs.token as HTMLElement).focus();
                    })
                })
                .catch((err: AxiosError) => {
                    if (!err.response) {
                        this.$flash.error(err.message);
                        console.error(err);
                        return;
                    }

                    const response = err.response;
                    if (response.data && isObject(response.data.errors)) {
                        response.data.errors.forEach((error: any) => {
                            this.$flash.error(error.detail);
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
         * @private
         */
        _callInternalApi: function (route: string, langKey: string) {
            this.$flash.clear();
            this.spinner = true;

            window.axios.post(this.route(route), {token: this.token})
                .then((response: AxiosResponse) => {
                    if (response.data.success) {
                        this.$flash.success(this.$t(`dashboard.account.two_factor.${langKey}`));
                    } else {
                        this.$flash.error(this.$t('dashboard.account.two_factor.invalid'));
                    }
                })
                .catch((error: AxiosError) => {
                    if (!error.response) {
                        this.$flash.error(error.message);
                        return;
                    }

                    const response = error.response;
                    if (response.data && isObject(response.data.errors)) {
                        response.data.errors.forEach((e: any) => {
                            this.$flash.error(e.detail);
                        });
                    }
                })
                .then(() => {
                    this.spinner = false;
                    this.$emit('close');
                });
        }
    },

    template: `
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
    `
})
