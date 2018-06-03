<template>
    <form class="bg-white shadow-lg rounded-lg pt-10 px-8 pb-6 mb-4 animate fadein" method="post"
          v-on:submit.prevent="submitToken"
    >
        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="input-open">
                <input class="input" id="grid-code" type="number" name="token" aria-labelledby="grid-username" required
                       ref="code"
                       :class="{ 'has-content' : code.length > 0 }"
                       v-model="code"
                />
                <label for="grid-code">{{ $t('auth.two_factor.label') }}</label>
                <p class="text-grey-darker text-xs">{{ $t('auth.two_factor.label_help') }}</p>
            </div>
        </div>
        <div>
            <button class="btn btn-blue btn-jumbo" type="submit">
                {{ $t('auth.sign_in') }}
            </button>
        </div>
        <div class="pt-6 text-center">
            <router-link class="text-xs text-grey tracking-wide no-underline uppercase hover:text-grey-dark"
                         :to="{ name: 'login' }"
            >
                Back to Login
            </router-link>
        </div>
    </form>
</template>

<script>
    export default {
        name: "two-factor-form",
        data: function () {
            return {
                code: '',
            };
        },
        mounted: function () {
            if ((this.$route.query.token || '').length < 1) {
                return this.$router.push({ name: 'login' });
            }

            this.$refs.code.focus();
        },
        methods: {
            submitToken: function () {
                const self = this;

                self.clearFlashes();
                axios.post(this.route('auth.login-checkpoint'), {
                    confirmation_token: this.$route.query.token,
                    authentication_code: this.$data.code,
                })
                    .then(function (response) {
                        if (!(response.data instanceof Object)) {
                            throw new Error('An error was encountered while processing this login.');
                        }

                        window.location = response.data.intended;
                    })
                    .catch(function (err) {
                        if (!err.response) {
                            return console.error(err);
                        }

                        const response = err.response;
                        if (response.data && _.isObject(response.data.errors)) {
                            response.data.errors.forEach(function (error) {
                                self.error(error.detail);
                            });
                            self.$router.push({ name: 'login' });
                        }
                    });
            }
        }
    }
</script>
