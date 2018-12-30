<template>
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
</template>

<script lang="ts">
    import Flash from '../Flash.vue';
    import ForgotPassword from "./ForgotPassword.vue";
    import LoginForm from "./LoginForm.vue";
    import TwoFactorForm from "./TwoFactorForm.vue";
    import Vue from 'vue';

    export default Vue.extend({
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
            Flash,
            TwoFactorForm,
            ForgotPassword,
            LoginForm,
        },
    });
</script>
