<template>
    <Modal :isVisible="isVisible" v-on:close="isVisible = false" :dismissable="!isLoading">
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
                <button class="btn btn-secondary btn-sm" v-on:click.prevent="isVisible = false">Cancel</button>
                <button class="btn btn-red btn-sm ml-2" v-on:click="deleteItem" :disabled="isLoading">
                    <span v-if="isLoading" class="spinner white">&nbsp;</span>
                    <span v-else>Yes, Delete</span>
                </button>
            </div>
        </div>
    </Modal>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Modal from '@/components/core/Modal.vue';
    import {DirectoryContentObject} from "@/api/server/types";
    import {deleteFile} from '@/api/server/files/deleteFile';
    import {mapState} from "vuex";
    import {AxiosError} from "axios";
    import { join } from 'path';
    import {ApplicationState} from '@/store/types';

    type DataStructure = {
        isLoading: boolean,
        error: string | null,
    };

    export default Vue.extend({
        name: 'DeleteFileModal',
        components: {Modal},

        props: {
            visible: { type: Boolean, default: false },
            object: { type: Object as () => DirectoryContentObject, required: true }
        },

        data: function (): DataStructure {
            return {
                isLoading: false,
                error: null,
            };
        },

        computed: {
            ...mapState({
                server: (state: ApplicationState) => state.server.server,
                credentials: (state: ApplicationState) => state.server.credentials,
                fm: (state: ApplicationState) => state.server.fm,
            }),

           isVisible: {
                get: function (): boolean {
                    return this.visible;
                },
                set: function (value: boolean) {
                    this.$emit('update:visible', value);
                },
            },
        },

        methods: {
            deleteItem: function () {
                this.isLoading = true;

                // @ts-ignore
                deleteFile(this.server.uuid, join(this.fm.currentDirectory, this.object.name))
                    .then(() => this.$emit('deleted'))
                    .catch((error: AxiosError) => {
                        this.error = `There was an error deleting the requested ${(this.object.directory) ? 'folder' : 'file'}. Response was: ${error.message}`;
                        console.error('Error at Server::Files::Delete', {error});
                    })
                    .then(() => this.isLoading = false);
            },
        },
    });
</script>
