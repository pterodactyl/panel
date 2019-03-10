<template>
    <Modal :show="isVisible" v-on:close="closeModal" :showCloseIcon="false" :dismissable="!isLoading">
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
                        :placeholder="object.name"
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
                        Rename
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
    import {ApplicationState} from "@/store/types";

    type DataStructure = {
        error: null | string,
        newName: string,
        isLoading: boolean,
    };

    export default Vue.extend({
        name: 'RenameModal',
        components: { Flash, Modal, MessageBox },

        props: {
            visible: { type: Boolean, default: false },
            object: { type: Object as () => DirectoryContentObject, required: true },
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

        watch: {
            visible: function (newVal, oldVal) {
                if (newVal && newVal !== oldVal) {
                    this.$nextTick(() => {
                        if (this.$refs.elementNameField) {
                            (this.$refs.elementNameField as HTMLInputElement).focus();
                        }
                    });
                }
            }
        },

        data: function (): DataStructure {
            return {
                newName: '',
                error: null,
                isLoading: false,
            };
        },

        methods: {
            submit: function () {
                this.isLoading = true;
                this.error = null;

                // @ts-ignore
                renameElement(this.server.uuid, this.credentials, {
                    // @ts-ignore
                    path: this.fm.currentDirectory,
                    toName: this.newName,
                    fromName: this.object.name
                })
                    .then(() => {
                        this.$emit('renamed', this.newName);
                        this.closeModal();
                    })
                    .catch((error: AxiosError) => {
                        this.error = `There was an error while renaming the requested ${this.object.file ? 'file' : 'folder'}. Response: ${error.message}`;
                        console.error('Error at Server::Files::Rename', { error });
                    })
                    .then(() => this.isLoading = false);
            },

            closeModal: function () {
                this.newName = '';
                this.error = null;
                this.isVisible = false;
            },
        },
    });
</script>
