<template>
    <div :class>
        <form method="post" v-on:submit.prevent="submitForm">
            <div class="bg-white p-6 border border-grey-light rounded rounded-1">
                <h2 class="mb-6 text-grey-darkest font-medium">Update your email</h2>
                <div>
                    <label for="grid-email" class="input-label">Email address</label>
                    <input id="grid-email" name="email" type="email" class="input" required
                        v-model="email"
                    >
                    <p class="input-help">If your email is no longer {{ user.email }} enter a new email in the field above.</p>
                </div>
                <div class="mt-6">
                    <label for="grid-password" class="input-label">Password</label>
                    <input id="grid-password" name="password" type="password" class="input" required
                        v-model="password"
                    >
                </div>
                <div class="mt-6">
                    <label for="grid-password-confirm" class="input-label">Confirm password</label>
                    <input id="grid-password-confirm" name="password_confirmation" type="password" class="input" required
                        v-model="confirm"
                    >
                </div>
                <div class="mt-6 text-right">
                    <button class="btn btn-blue btn-sm text-right" type="submit">Save</button>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
    import { mapState, mapActions } from 'vuex';

    export default {
        name: 'update-email',
        data: function () {
            return {
                email: '',
                password: '',
                confirm: '',
            };
        },
        computed: {
            ...mapState({
                user: state => state.auth.user,
            })
        },

        methods: {
            /**
             * Update a user's email address on the Panel.
             */
            submitForm: function () {
                this.clearFlashes();
                this.updateEmail({
                    email: this.$data.email,
                    password: this.$data.password,
                    confirm: this.$data.confirm,
                })
                    .then(() => {
                        this.success('Your email address has been updated.');
                    })
                    .catch(error => {
                        if (!error.response) {
                            return console.error(error);
                        }

                        const response = error.response;
                        if (response.data && _.isObject(response.data.errors)) {
                            response.data.errors.forEach(e => {
                                this.error(e.detail);
                            });
                        }
                    });
            },

            ...mapActions('auth', [
                'updateEmail',
            ])
        }
    };
</script>

<style scoped>

</style>
