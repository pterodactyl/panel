<template>
    <div class="lg:inline-flex" role="alert">
        <span class="title" v-html="title" v-if="title && title.length > 0"></span>
        <span class="message" v-html="message"></span>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';

    export default Vue.extend({
        name: 'MessageBox',
        props: {
            title: {type: String, required: false},
            message: {type: String, required: true}
        },
    });
</script>
