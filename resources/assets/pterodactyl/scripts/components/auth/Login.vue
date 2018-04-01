<template>
    <div>
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
        <two-factor-form
            v-if="this.$route.name === 'checkpoint'"
        />
    </div>
</template>

<script>
    import LoginForm from "./LoginForm";
    import ForgotPassword from "./ForgotPassword";
    import TwoFactorForm from "./TwoFactorForm";

    export default {
        name: 'login',
        data: function () {
            return {
                user: {
                    email: ''
                },
            };
        },
        methods: {
            onUpdateEmail: function (value) {
                this.$data.user.email = value;
            },
        },
        components: {
            TwoFactorForm,
            ForgotPassword,
            LoginForm,
        },
    }
</script>
