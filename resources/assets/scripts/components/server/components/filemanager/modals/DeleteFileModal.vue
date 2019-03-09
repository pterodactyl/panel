<template>
    <Modal :show="visible" v-on:close="visible = false">
        <MessageBox
            class="alert error mb-8"
            title="Error"
            :message="error"
            v-if="error"
        />
        <div v-if="object">
            <h3 class="font-medium mb-6">Really delete {{ object.name }}?</h3>
            <p class="text-sm text-neutral-700">
                Deletion is a permanent operation: <strong>{{ object.name }}</strong><span v-if="object.directory">, as well as its contents,</span> will be removed immediately.
            </p>
            <div class="mt-8 text-right">
                <button class="btn btn-secondary btn-sm" v-on:click.prevent="visible = false">Cancel</button>
                <button class="btn btn-red btn-sm ml-2" v-on:click="deleteItem">Yes, Delete</button>
            </div>
        </div>
    </Modal>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Modal from '@/components/core/Modal.vue';
    import {DirectoryContentObject} from "@/api/server/types";
    import {deleteElement} from '@/api/server/files/deleteElement';
    import {mapState} from "vuex";
    import {AxiosError} from "axios";
    import { join } from 'path';

    type DataStructure = {
        isLoading: boolean,
        object: null | DirectoryContentObject,
        visible: boolean,
        error: string | null,
    };

    export default Vue.extend({
        name: 'DeleteFileModal',
        components: {Modal},

        data: function (): DataStructure {
            return {
                isLoading: false,
                visible: false,
                object: null,
                error: null,
            };
        },

        computed: {
            ...mapState('server', ['fm', 'server', 'credentials']),
        },

        mounted: function () {
            window.events.$on('server:files:delete', (object: DirectoryContentObject) => {
                this.visible = true;
                this.object = object;
            });
        },

        beforeDestroy: function () {
            window.events.$off('server:files:delete');
        },

        methods: {
            deleteItem: function () {
                if (!this.object) {
                    return;
                }

                this.isLoading = true;

                deleteElement(this.server.uuid, this.credentials, [
                    join(this.fm.currentDirectory, this.object.name)
                ])
                    .then(() => {
                        this.$emit('close');
                        this.closeModal();
                    })
                    .catch((error: AxiosError) => {
                        this.error = `There was an error deleting the requested ${(this.object && this.object.directory) ? 'folder' : 'file'}. Response was: ${error.message}`;
                        console.error('Error at Server::Files::Delete', { error });
                    })
                    .then(() => this.isLoading = false);
            },

            closeModal: function () {
                this.object = null;
                this.isLoading = false;
                this.visible = false;
                this.error = null;
            },
        },
    });
</script>
