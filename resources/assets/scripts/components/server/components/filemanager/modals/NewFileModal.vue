<template>
    <Modal :show="isVisible" v-on:close="isVisible = false" :dismissable="!isLoading">
        <MessageBox class="alert error mb-8" title="Error" :message="error" v-if="error"/>
    </Modal>
</template>

<script lang="ts">
    import Vue from 'vue';
    import MessageBox from "@/components/MessageBox.vue";
    import Modal from "@/components/core/Modal.vue";
    import {ApplicationState} from '@/store/types';
    import {mapState} from "vuex";

    export default Vue.extend({
        name: 'NewFileModal',

        components: {MessageBox, Modal},

        data: function (): { error: string | null, isVisible: boolean, isLoading: boolean } {
            return {
                error: null,
                isVisible: false,
                isLoading: false,
            };
        },

        computed: mapState({
            fm: (state: ApplicationState) => state.server.fm,
        }),

        mounted: function () {
            window.events.$on('server:files:open-new-file-modal', () => {
                this.isVisible = true;
            });
        },

        methods: {
            submit: function () {

            },
        }
    })
</script>
