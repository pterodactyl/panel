<template>
    <transition name="modal">
        <div class="modal-mask" v-show="visible">
            <div class="modal-container w-auto">
                <div class="modal-content p-8 pb-0">
                    <div class="spinner spinner-thick spinner-relative blue spinner-xl"></div>
                    <p class="text-neutral-700 mt-8 text-sm">
                        <slot/>
                    </p>
                </div>
            </div>
        </div>
    </transition>
</template>

<script lang="ts">
    import Vue from 'vue';

    export default Vue.extend({
        props: {
            visible: { type: Boolean, default: false },
        },
    });
</script>
