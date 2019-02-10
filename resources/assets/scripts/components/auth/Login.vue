<template>
    <div>
        <Flash container="mb-2"/>
        <div>
            <router-view/>
        </div>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Flash from "../Flash.vue";

    export default Vue.extend({
        name: 'Login',
        components: {Flash},
    });
</script>
