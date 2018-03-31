<template>
    <div>
        <login-form
                v-if="this.$route.path === '/'"
                v-bind:email="email"
                v-on:update-email="onEmailUpdate"
        />
        <forgot-password
                v-if="this.$route.path === '/forgot-password'"
                v-bind:email="email"
                v-on:update-email="onEmailUpdate"
        />
    </div>
</template>

<script>
    import LoginForm from "./LoginForm";
    import ForgotPassword from "./ForgotPassword";

    export default {
        name: 'login',
        data: function () {
            return {
                email: '',
            };
        },
        methods: {
            onEmailUpdate: function (value) {
                this.$data.email = value;
            },
        },
        components: {
            ForgotPassword,
            LoginForm
        },
    }
</script>
