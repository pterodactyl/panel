<template>
    <transition name="modal">
        <div class="modal-mask" v-show="show" v-on:click="close">
            <div class="modal-container" @click.stop>
                <x-icon class="absolute pin-r pin-t m-2 text-grey cursor-pointer" aria-label="Close modal" role="button"
                        v-on:click="close"
                />
                <slot/>
            </div>
        </div>
    </transition>
</template>

<script lang="ts">
    import { XIcon } from 'vue-feather-icons';
    export default {
        name: 'modal',
        components: { XIcon },
        props: {
            modalName: { type: String, default: 'modal' },
            show: { type: Boolean, default: false },
            closeOnEsc: { type: Boolean, default: true },
        },
        mounted: function () {
            if (this.$props.closeOnEsc) {
                document.addEventListener('keydown', e => {
                    if (this.show && e.key === 'Escape') {
                        this.close();
                    }
                })
            }
        },

        methods: {
            close: function () {
                this.$emit('close', this.$props.modalName);
            }
        }
    };
</script>
