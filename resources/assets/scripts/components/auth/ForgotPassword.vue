<template>
    <div>
        <form class="bg-white shadow-lg rounded-lg pt-10 px-8 pb-6 mb-4 animate fadein" method="post"
              v-on:submit.prevent="submitForm"
        >
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-email" type="email" aria-labelledby="grid-email" ref="email" required
                           v-bind:class="{ 'has-content': email.length > 0 }"
                           v-bind:readonly="showSpinner"
                           v-bind:value="email"
                           v-on:input="updateEmail($event)"
                    />
                    <label for="grid-email">{{ $t('strings.email') }}</label>
                    <p class="text-grey-darker text-xs">{{ $t('auth.forgot_password.label_help') }}</p>
                </div>
            </div>
            <div>
                <button class="btn btn-blue btn-jumbo" type="submit" v-bind:disabled="submitDisabled">
                    <span class="spinner white" v-bind:class="{ hidden: ! showSpinner }">&nbsp;</span>
                    <span v-bind:class="{ hidden: showSpinner }">
                        {{ $t('auth.forgot_password.button') }}
                    </span>
                </button>
            </div>
            <div class="pt-6 text-center">
                <router-link class="text-xs text-grey tracking-wide no-underline uppercase hover:text-grey-dark"
                             :to="{ name: 'login' }"
                >
                    {{ $t('auth.go_to_login') }}
                </router-link>
            </div>
        </form>
    </div>
</template>

<script>
    export default {
        name: 'forgot-password',
        props: {
            email: {type: String, required: true},
        },
        mounted: function () {
            this.$refs.email.focus();
        },
        data: function () {
            return {
                X_CSRF_TOKEN: window.X_CSRF_TOKEN,
                errors: [],
                submitDisabled: false,
                showSpinner: false,
            };
        },
        methods: {
            updateEmail: function (event) {
                this.$data.submitDisabled = false;
                this.$emit('update-email', event.target.value);
            },

            submitForm: function () {
                const self = this;
                this.$data.submitDisabled = true;
                this.$data.showSpinner = true;
                this.$data.errors = [];

                this.clearFlashes();
                window.axios.post(this.route('auth.forgot-password'), {
                    email: this.$props.email,
                })
                    .then(function (response) {
                        if (!(response.data instanceof Object)) {
                            throw new Error('An error was encountered while processing this request.');
                        }

                        self.$data.submitDisabled = false;
                        self.$data.showSpinner = false;
                        self.success(response.data.status);
                        self.$router.push({name: 'login'});
                    })
                    .catch(function (err) {
                        self.$data.showSpinner = false;
                        if (!err.response) {
                            return console.error(err);
                        }

                        const response = err.response;
                        if (response.data && _.isObject(response.data.errors)) {
                            response.data.errors.forEach(function (error) {
                                self.error(error.detail);
                            });
                        }
                    });
            }
        }
    }
</script>
