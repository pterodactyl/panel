<template>
    <transition name="modal">
        <div class="modal-mask" v-show="isVisible" v-on:click="closeOnBackground && close()">
            <div class="modal-container p-8" :class="{ 'full-screen': isFullScreen }" @click.stop>
                <div class="modal-close-icon" v-on:click="close" v-if="dismissable && showCloseIcon">
                    <Icon name="x" aria-label="Close modal" role="button"/>
                </div>
                <div class="modal-content">
                    <slot/>
                </div>
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
            isVisible: {type: Boolean, default: false},
            closeOnEsc: {type: Boolean, default: true},
            dismissable: {type: Boolean, default: true},
            showCloseIcon: {type: Boolean, default: true},
            isFullScreen: {type: Boolean, default: false},
            closeOnBackground: {type: Boolean, default: true},
        },

        mounted: function () {
            if (this.$props.closeOnEsc) {
                document.addEventListener('keydown', e => {
                    if (this.isVisible && e.key === 'Escape') {
                        this.close();
                    }
                })
            }
        },

        methods: {
            close: function () {
                if (!this.$props.dismissable) {
                    return;
                }

                this.$emit('close', this.$props.modalName);
            }
        },
    });
</script>
