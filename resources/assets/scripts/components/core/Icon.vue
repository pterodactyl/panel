<template>
    <i :data-feather="name"></i>
</template>

<script lang="ts">
    import Vue from 'vue';
    import {replace} from 'feather-icons';

    export default Vue.extend({
        name: 'Icon',
        props: {
            name: {type: String, default: 'circle'},
        },
        mounted: function () {
            replace();
        },
    });
</script>
