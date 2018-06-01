<template>
    <div>
        <form class="bg-white shadow-lg rounded-lg pt-10 px-8 pb-6 mb-4 animate fadein" method="post"
              v-on:submit.prevent="submitForm"
        >
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-username" type="text" name="user" aria-labelledby="grid-username-label" required
                           ref="email"
                           :class="{ 'has-content' : user.email.length > 0 }"
                           :readonly="showSpinner"
                           :value="user.email"
                           v-on:input="updateEmail($event)"
                    />
                    <label id="grid-username-label" for="grid-username">{{ $t('strings.user_identifier') }}</label>
                </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-password" type="password" name="password" aria-labelledby="grid-password-label" required
                           ref="password"
                           :class="{ 'has-content' : user.password && user.password.length > 0 }"
                           :readonly="showSpinner"
                           v-model="user.password"
                    />
                    <label id="grid-password-label" for="grid-password">{{ $t('strings.password') }}</label>
                </div>
            </div>
            <div>
                <button id="grid-login-button" class="btn btn-blue btn-jumbo" type="submit" aria-label="Log in"
                        v-bind:disabled="showSpinner">
                    <span class="spinner white" v-bind:class="{ hidden: ! showSpinner }">&nbsp;</span>
                    <span v-bind:class="{ hidden: showSpinner }">
                        {{ $t('auth.sign_in') }}
                    </span>
                </button>
            </div>
            <div class="pt-6 text-center">
                <router-link class="text-xs text-grey tracking-wide no-underline uppercase hover:text-grey-dark" aria-label="Forgot password"
                             :to="{ name: 'forgot-password' }">
                    {{ $t('auth.forgot_password.label') }}
                </router-link>
            </div>
        </form>
    </div>
</template>

<script>
    export default {
        name: 'login-form',
        props: {
            user: {
                type: Object,
                required: false,
                default: function () {
                    return {
                        email: '',
                        password: '',
                    };
                },
            }
        },
        data: function () {
            return {
                errors: [],
                showSpinner: false,
            }
        },
        mounted: function () {
            this.$refs.email.focus();
        },
        methods: {
            // Handle a login request eminating from the form. If 2FA is required the
            // user will be presented with the 2FA modal window.
            submitForm: function () {
                const self = this;
                this.$data.showSpinner = true;

                this.clearFlashes();
                axios.post(this.route('auth.login'), {
                    user: this.$props.user.email,
                    password: this.$props.user.password,
                })
                    .then(function (response) {
                        if (response.data.complete) {
                            return window.location = '/';
                        }

                        self.$props.user.password = '';
                        self.$data.showSpinner = false;
                        self.$router.push({name: 'checkpoint', query: {token: response.data.token}});
                    })
                    .catch(function (err) {
                        self.$props.user.password = '';
                        self.$data.showSpinner = false;
                        if (!err.response) {
                            return console.error(err);
                        }

                        const response = err.response;
                        if (response.data && _.isObject(response.data.errors)) {
                            response.data.errors.forEach(function (error) {
                                self.error(error.detail);
                            });
                            self.$refs.password.focus();
                        }
                    });
            },

            // Update the email address associated with the login form
            // so that it is populated in the parent model automatically.
            updateEmail: function (event) {
                this.$emit('update-email', event.target.value);
            }
        }
    }
</script>
