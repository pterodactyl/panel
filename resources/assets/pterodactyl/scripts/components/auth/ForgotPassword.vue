<template>
    <div>
        <form class="bg-white shadow-lg rounded-lg pt-10 px-8 pb-6 mb-4 animate fadein" method="post">
            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="input-open">
                    <input class="input" id="grid-email" type="text" aria-labelledby="grid-email" required
                           v-bind:value="email"
                           v-on:input="updateEmail($event)"
                    />
                    <label for="grid-email">Email</label>
                    <p class="text-grey-darker text-xs">Enter your account email address to recive instructions on resetting your password.</p>
                </div>
            </div>
            <div>
                <button class="bg-blue hover:bg-blue-dark hover:border-blue-darker border-blue-dark border text-white p-4 rounded w-full uppercase tracking-wide text-sm"
                        type="submit">
                    Recover Account
                </button>
            </div>
            <div class="pt-6 text-center">
                <router-link to="/" class="text-xs text-grey tracking-wide no-underline uppercase hover:text-grey-dark">
                    Go to Login
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
        methods: {
            updateEmail: function (event) {
                this.$emit('update-email', event.target.value);
            }
        }
    }
</script>
