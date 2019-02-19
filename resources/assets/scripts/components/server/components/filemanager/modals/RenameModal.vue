<template>
    <Modal
        :show="visible"
        v-on:close="closeModal"
        :showCloseIcon="false"
        :dismissable="!isLoading"
    >
        <MessageBox
                class="alert error mb-8"
                title="Error"
                :message="error"
                v-if="error"
        />
        <div class="flex items-end" v-if="object">
            <div class="flex-1">
                <label class="input-label">
                    Rename {{ object.file ? 'File' : 'Folder' }}
                </label>
                <input
                        type="text" class="input" name="element_name"
                        ref="elementNameField"
                        v-model="newName"
                        v-validate.disabled="'required'"
                        v-validate="'alpha_dash'"
                        v-on:keyup.enter="submit"
                />
            </div>
            <div class="ml-4">
                <button type="submit"
                        class="btn btn-primary btn-sm"
                        v-on:click.prevent="submit"
                        :disabled="errors.any() || isLoading"
                >
                    <span class="spinner white" v-bind:class="{ hidden: !isLoading }">&nbsp;</span>
                    <span :class="{ hidden: isLoading }">
                        Edit
                    </span>
                </button>
            </div>
        </div>
        <p class="input-help error">
            {{ errors.first('folder_name') }}
        </p>
    </Modal>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Flash from '@/components/Flash.vue';
    import Modal from '@/components/core/Modal.vue';
    import MessageBox from '@/components/MessageBox.vue';
    import {DirectoryContentObject} from "@/api/server/types";
    import {mapState} from "vuex";
    import {renameElement} from "@/api/server/files/renameElement";
    import {AxiosError} from 'axios';

    type DataStructure = {
        object: null | DirectoryContentObject,
        error: null | string,
        newName: string,
        visible: boolean,
        isLoading: boolean,
    };

    export default Vue.extend({
        name: 'RenameModal',
        components: { Flash, Modal, MessageBox },

        computed: {
            ...mapState('server', ['fm', 'server', 'credentials']),
        },

        data: function (): DataStructure {
            return {
                object: null,
                newName: '',
                error: null,
                visible: false,
                isLoading: false,
            };
        },

        mounted: function () {
            window.events.$on('server:files:rename', (data: DirectoryContentObject): void => {
                this.visible = true;
                this.object = data;
                this.newName = data.name;

                this.$nextTick(() => {
                    if (this.$refs.elementNameField) {
                        (this.$refs.elementNameField as HTMLInputElement).focus();
                    }
                })
            });
        },

        beforeDestroy: function () {
            window.events.$off('server:files:rename');
        },

        methods: {
            submit: function () {
                if (!this.object) {
                    return;
                }

                this.isLoading = true;
                this.error = null;
                renameElement(this.server.uuid, this.credentials, {
                    path: this.fm.currentDirectory,
                    toName: this.newName,
                    fromName: this.object.name
                })
                    .then(() => {
                        if (this.object) {
                            this.object.name = this.newName;
                        }

                        this.closeModal();
                    })
                    .catch((error: AxiosError) => {
                        const t = this.object ? (this.object.file ? 'file' : 'folder') : 'item';

                        this.error = `There was an error while renaming the requested ${t}. Response: ${error.message}`;
                        console.error('Error at Server::Files::Rename', { error });
                    })
                    .then(() => this.isLoading = false);
            },

            closeModal: function () {
                this.object = null;
                this.newName = '';
                this.visible = false;
                this.error = null;
            },
        },
    });
</script>
