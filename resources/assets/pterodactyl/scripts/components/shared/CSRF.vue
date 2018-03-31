<template>
    <input type="hidden" name="_token" v-bind:value="X_CSRF_TOKEN" />
</template>

<script>
    export default {
        name: 'csrf',
        data: function () {
            return {
                X_CSRF_TOKEN: window.X_CSRF_TOKEN,
            };
        },
    }
</script>
