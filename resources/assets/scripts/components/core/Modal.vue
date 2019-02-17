<template>
    <transition name="modal">
        <div class="modal-mask" v-show="show" v-on:click="close">
            <div class="modal-container" @click.stop>
                <div v-on:click="close" v-if="dismissable">
                    <Icon name="x"
                          class="absolute pin-r pin-t m-2 text-neutral-500 cursor-pointer"
                          aria-label="Close modal"
                          role="button"
                    />
                </div>
                <slot/>
            </div>
        </div>
    </transition>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Icon from "./Icon.vue";

    export default Vue.extend({
        name: 'Modal',
        components: {Icon},

        props: {
            modalName: {type: String, default: 'modal'},
            show: {type: Boolean, default: false},
            closeOnEsc: {type: Boolean, default: true},
            dismissable: {type: Boolean, default: true},
        },

        mounted: function () {
            if (this.$props.dismissable && this.$props.closeOnEsc) {
                document.addEventListener('keydown', e => {
                    if (this.show && e.key === 'Escape') {
                        this.close();
                    }
                })
            }
        },

        methods: {
            close: function () {
                if (this.$props.dismissable) {
                    this.$emit('close', this.$props.modalName);
                }
            }
        },
    });
</script>
