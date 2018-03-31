<template>
    <div>
        <form class="bg-white shadow-lg rounded-lg pt-10 px-8 pb-6 mb-4 animate fadein" :action="route('auth.login')" method="post">
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-username" type="text" name="user" aria-labelledby="grid-username" required
                           v-bind:value="email"
                           v-on:input="updateEmail($event)"
                    />
                    <label for="grid-username">{{ $t('strings.user_identifier') }}</label>
                </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-password" type="password" name="password" aria-labelledby="grid-password" required>
                    <label for="grid-password">{{ $t('strings.password') }}</label>
                </div>
            </div>
            <div>
                <button class="btn btn-blue btn-jumbo" type="submit">
                    {{ $t('auth.sign_in') }}
                </button>
            </div>
            <div class="pt-6 text-center">
                <router-link to="/forgot-password" class="text-xs text-grey tracking-wide no-underline uppercase hover:text-grey-dark">
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
            email: { type: String, required: true },
        },
        methods: {
            updateEmail: function (event) {
                this.$emit('update-email', event.target.value);
            }
        }
    }
</script>
