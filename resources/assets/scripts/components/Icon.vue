<template>
    <i class="ion" :class="iconClass"></i>
</template>

<script>
    export default {
        props: {
            type: {type: String, required: true},
            iconStyle: {type: String, default: 'ios'},
        },

        computed: {
            iconClass: function () {
                return `ion-${this.iconStyle}-${this.type}`;
            }
        },
    }
</script>
