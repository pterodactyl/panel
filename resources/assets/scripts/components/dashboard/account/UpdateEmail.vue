<template>
    <div :class>
        <form method="post" v-on:submit.prevent="submitForm">
            <div class="content-box">
                <h2 class="mb-6 text-grey-darkest font-medium">{{ $t('dashboard.account.email.title') }}</h2>
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
</template>

<script>
    import { isObject, get } from 'lodash';
    import { mapState, mapActions } from 'vuex';

    export default {
        name: 'update-email',
        data: function () {
            return {
                email: get(this.$store.state, 'auth.user.email', ''),
                password: '',
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
                    password: this.$data.password
                })
                    .finally(() => {
                        this.$data.password = '';
                    })
                    .then(() => {
                        this.success(this.$t('dashboard.account.email.updated'));
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
                    });
            },

            ...mapActions('auth', [
                'updateEmail',
            ])
        }
    };
</script>
