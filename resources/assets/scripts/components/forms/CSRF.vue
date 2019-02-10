<template>
    <input type="hidden" name="_token" v-bind:value="X_CSRF_TOKEN" />
</template>

<script lang="ts">
    import Vue from 'vue';

    export default Vue.extend({
        name: 'CSRF',
        data: function () {
            return {
                X_CSRF_TOKEN: window.X_CSRF_TOKEN,
            };
        },
    });
</script>
