import Vue from 'vue';
import { get, isObject } from 'lodash';
import { mapState } from 'vuex';
import {ApplicationState} from "../../../store/types";
import {AxiosError} from "axios";

export default Vue.component('update-email', {
    data: function () {
        return {
            email: get(this.$store.state, 'auth.user.email', ''),
            password: '',
        };
    },

    computed: {
        ...mapState({
            user: (state: ApplicationState) => state.auth.user,
        })
    },

    methods: {
        /**
         * Update a user's email address on the Panel.
         */
        submitForm: function () {
            this.$flash.clear();
            this.$store.dispatch('auth/updateEmail', { email: this.email, password: this.password })
                .then(() => {
                    this.$flash.success(this.$t('dashboard.account.email.updated'));
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
                    this.$data.password = '';
                });
        },
    },

    template: `
        <div id="update-email-container" :class>
            <form method="post" v-on:submit.prevent="submitForm">
                <div class="content-box">
                    <h2 class="mb-6 text-neutral-900 font-medium">{{ $t('dashboard.account.email.title') }}</h2>
                    <div>
                        <label for="grid-email" class="input-label">{{ $t('strings.email_address') }}</label>
                        <input id="grid-email" name="email" type="email" class="input" required
                               :class="{ error: errors.has('email') }"
                               v-validate
                               v-model="email"
                        >
                        <p class="input-help error" v-show="errors.has('email')">{{ errors.first('email') }}</p>
                    </div>
                    <div class="mt-6">
                        <label for="grid-password" class="input-label">{{ $t('strings.password') }}</label>
                        <input id="grid-password" name="password" type="password" class="input" required
                            v-model="password"
                        >
                    </div>
                    <div class="mt-6 text-right">
                        <button class="btn btn-blue btn-sm text-right" type="submit">{{ $t('strings.save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    `,
});
