<template>
    <div>
        <div class="pb-4" v-for="error in errors">
            <div class="p-2 bg-red-dark border-red-darker border items-center text-red-lightest leading-normal rounded flex lg:inline-flex w-full text-sm"
                 role="alert">
                <span class="flex rounded-full bg-red uppercase px-2 py-1 text-xs font-bold mr-3 leading-none">Error</span>
                <span class="mr-2 text-left flex-auto">{{ error }}</span>
            </div>
        </div>
        <form class="bg-white shadow-lg rounded-lg pt-10 px-8 pb-6 mb-4 animate fadein" method="post"
              v-on:submit.prevent="submitForm"
        >
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-email" type="email" aria-labelledby="grid-email" required
                           ref="email"
                           v-bind:class="{ 'has-content': email.length > 0 }"
                           v-bind:readonly="showSpinner"
                           v-on:input="updateEmailField"
                    />
                    <label for="grid-email">{{ $t('strings.email') }}</label>
                </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-password" type="password" aria-labelledby="grid-password" required
                           ref="password"
                           v-bind:readonly="showSpinner"
                           v-model="password"
                    />
                    <label for="grid-password">{{ $t('strings.password') }}</label>
                    <p class="text-grey-darker text-xs">{{ $t('auth.password_requirements') }}</p>
                </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-password-confirmation" type="password" aria-labelledby="grid-password-confirmation" required
                           v-bind:readonly="showSpinner"
                           v-model="passwordConfirmation"
                    />
                    <label for="grid-password-confirmation">{{ $t('strings.confirm_password') }}</label>
                </div>
            </div>
            <div>
                <button class="btn btn-blue btn-jumbo" type="submit" v-bind:class="{ disabled: showSpinner }">
                    <span class="spinner white" v-bind:class="{ hidden: ! showSpinner }">&nbsp;</span>
                    <span v-bind:class="{ hidden: showSpinner }">
                        {{ $t('auth.reset_password.button') }}
                    </span>
                </button>
            </div>
            <div class="pt-6 text-center">
                <router-link to="/" class="text-xs text-grey tracking-wide no-underline uppercase hover:text-grey-dark">
                    {{ $t('auth.go_to_login') }}
                </router-link>
            </div>
        </form>
    </div>
</template>

<script>
    export default {
        name: "ResetPassword",
        props: {
            token: {type: String, required: true},
            email: {type: String, required: false},
        },
        mounted: function () {
            if (this.$props.email.length > 0) {
                this.$refs.email.value = this.$props.email;
                return this.$refs.password.focus();
            }
        },
        data: function () {
            return {
                errors: [],
                showSpinner: false,
                password: '',
                passwordConfirmation: '',
            };
        },
        methods: {
            updateEmailField: function (event) {
                this.$data.submitDisabled = event.target.value.length === 0;
            },

            submitForm: function () {
                const self = this;
                this.$data.showSpinner = true;

                window.axios.post(this.route('auth.reset-password'), {
                    email: this.$props.email,
                    password: this.$data.password,
                    password_confirmation: this.$data.passwordConfirmation,
                    token: this.$props.token,
                })
                    .then(function (response) {
                        return window.location = response.data.redirect_to;
                    })
                    .catch(function (err) {
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
            }
        }
    }
</script>
