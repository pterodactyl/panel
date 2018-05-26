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

                window.axios.post(this.route('auth.forgot-password'), {
                    email: this.$props.email,
                })
                    .then(function (response) {
                        self.$data.submitDisabled = false;
                        self.$data.showSpinner = false;
                        self.flash({message: response.data.status, variant: 'success'});
                        self.$router.push({name: 'login'});
                    })
                    .catch(function (err) {
                        self.$data.showSpinner = false;
                        if (!err.response) {
                            return console.error(err);
                        }

                        const response = err.response;
                        if (response.data && _.isObject(response.data.errors)) {
                            self.$data.errors.push(response.data.errors[0].detail);
                        }
                    });
            }
        }
    }
</script>
