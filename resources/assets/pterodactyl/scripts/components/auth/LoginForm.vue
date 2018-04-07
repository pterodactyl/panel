<template>
    <div>
        <flash-message variant="danger" />
        <flash-message variant="success" />
        <div class="pb-4" v-if="errors && errors.length === 1">
            <div class="p-2 bg-red-dark border-red-darker border items-center text-red-lightest leading-normal rounded flex lg:inline-flex w-full text-sm"
                 role="alert">
                <span class="flex rounded-full bg-red uppercase px-2 py-1 text-xs font-bold mr-3 leading-none">Error</span>
                <span class="mr-2 text-left flex-auto">{{ errors[0] }}</span>
            </div>
        </div>
        <form class="bg-white shadow-lg rounded-lg pt-10 px-8 pb-6 mb-4 animate fadein" method="post"
              v-on:submit.prevent="submitForm"
        >
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-username" type="text" name="user" aria-labelledby="grid-username" required
                           ref="email"
                           v-bind:value="user.email"
                           v-on:input="updateEmail($event)"
                    />
                    <label for="grid-username">{{ $t('strings.user_identifier') }}</label>
                </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-password" type="password" name="password"
                           ref="password"
                           aria-labelledby="grid-password" required
                           v-model="user.password"
                    />
                    <label for="grid-password">{{ $t('strings.password') }}</label>
                </div>
            </div>
            <div>
                <button class="btn btn-blue btn-jumbo" type="submit" v-bind:disabled="showSpinner">
                    <span class="spinner white" v-bind:class="{ hidden: ! showSpinner }">&nbsp;</span>
                    <span v-bind:class="{ hidden: showSpinner }">
                        {{ $t('auth.sign_in') }}
                    </span>
                </button>
            </div>
            <div class="pt-6 text-center">
                <router-link class="text-xs text-grey tracking-wide no-underline uppercase hover:text-grey-dark"
                             :to="{ name: 'forgot-password' }">
                    {{ $t('auth.forgot_password') }}
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
                            self.$data.errors = [response.data.errors[0].detail];
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
