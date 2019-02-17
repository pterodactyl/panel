<template>
    <Modal :show="visible" v-on:close="visible = false">
        <div>
            <h2 class="font-medium text-neutral-900 mb-6">{{ fm.currentDirectory }}</h2>
        </div>
    </Modal>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Modal from '@/components/core/Modal.vue';
    import {mapState} from "vuex";

    export default Vue.extend({
        name: 'CreateFolderModal',
        components: {Modal},

        computed: {
            ...mapState('server', ['fm']),
        },

        data: function () {
            return {
                visible: false,
            };
        },

        mounted: function () {
            window.events.$on('server:files:open-directory-modal', () => {
                this.visible = true;
            });
        },
    });
</script>
