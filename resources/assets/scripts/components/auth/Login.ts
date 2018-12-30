import Vue from 'vue';
import LoginForm from "./LoginForm";
import ForgotPassword from "./ForgotPassword";
import TwoFactorForm from "./TwoFactorForm";
import Flash from "../Flash";

export default Vue.component('login', {
    data: function () {
        return {
            user: {
                email: ''
            },
        };
    },
    components: {
        Flash,
        LoginForm,
        ForgotPassword,
        TwoFactorForm,
    },
    methods: {
        onUpdateEmail: function (value: string) {
            this.$data.user.email = value;
        },
    },
    template: `
        <div>
            <flash container="mb-2"/>
            <login-form
                    v-if="this.$route.name === 'login'"
                    v-bind:user="user"
                    v-on:update-email="onUpdateEmail"
            />
            <forgot-password
                    v-if="this.$route.name === 'forgot-password'"
                    v-bind:email="user.email"
                    v-on:update-email="onUpdateEmail"
            />
            <two-factor-form v-if="this.$route.name === 'checkpoint'" />
        </div>
    `,
});
