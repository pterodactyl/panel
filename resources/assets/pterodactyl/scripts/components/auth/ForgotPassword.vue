<template>
    <div>
        <form class="bg-white shadow-lg rounded-lg pt-10 px-8 pb-6 mb-4 animate fadein" method="post">
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-email" type="email" aria-labelledby="grid-email" required
                           v-bind:value="email"
                           v-on:input="updateEmail($event)"
                    />
                    <label for="grid-email">{{ $t('strings.email') }}</label>
                    <p class="text-grey-darker text-xs">{{ $t('auth.reset_help_text') }}</p>
                </div>
            </div>
            <div>
                <csrf/>
                <button class="btn btn-blue btn-jumbo" type="submit">
                    {{ $t('auth.recover_account') }}
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
    import Csrf from "../shared/CSRF";

    export default {
        components: {Csrf},
        name: 'forgot-password',
        props: {
            email: {type: String, required: true},
        },
        data: function () {
            return {
                X_CSRF_TOKEN: window.X_CSRF_TOKEN,
            };
        },
        methods: {
            updateEmail: function (event) {
                this.$emit('update-email', event.target.value);
            }
        }
    }
</script>
