<template>
    <Modal :isVisible="visible" v-on:close="isVisible = false" :dismissable="!isLoading">
        <MessageBox class="alert error mb-8" title="Error" :message="error" v-if="error"/>
        <div class="flex items-end">
            <div class="flex-1">
                <label class="input-label">
                    Move {{ file.name}}
                </label>
                <input
                        type="text" class="input" name="move_to"
                        :placeholder="file.name"
                        ref="moveToField"
                        v-model="moveTo"
                        v-validate="{ required: true, regex: /(^[\w\d.\-\/]+$)/}"
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
                        Move {{ file.directory ? 'Folder' : 'File' }}
                    </span>
                </button>
            </div>
        </div>
        <p class="input-help error" v-if="errors.count()">
            {{ errors.first('move_to') }}
        </p>
        <p class="input-help" v-else>
            Enter the new name and path for this {{ file.directory ? 'folder' : 'file' }} in the field above. This will be relative to the current directory.
        </p>
    </Modal>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Modal from "@/components/core/Modal.vue";
    import MessageBox from "@/components/MessageBox.vue";
    import {DirectoryContentObject} from "@/api/server/types";
    import {renameFile} from '@/api/server/files/renameFile';
    import {mapState} from "vuex";
    import {ApplicationState} from "@/store/types";
    import {join} from 'path';
    import {AxiosError} from "axios";

    type DataStructure = {
        error: null | string,
        isLoading: boolean,
        moveTo: null | string,
    };

    export default Vue.extend({
        name: 'MoveFileModal',

        components: { MessageBox, Modal },

        data: function (): DataStructure {
            return {
                error: null,
                isLoading: false,
                moveTo: null,
            };
        },

        props: {
            visible: { type: Boolean, default: false },
            file: { type: Object as () => DirectoryContentObject, required: true }
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
                    this.$emit('update:visible', value)
                },
            }
        },

        watch: {
            isVisible: function (n, o): void {
                if (n !== o) {
                    this.resetModal();
                }

                if (n && !o) {
                    this.$nextTick(() => (this.$refs.moveToField as HTMLElement).focus());
                }
            },
        },

        methods: {
            submit: function () {
                this.isLoading = true;

                // @ts-ignore
                renameFile(this.server.uuid, join(this.fm.currentDirectory, this.file.name), join(this.fm.currentDirectory, this.moveTo))
                    .then(() => this.$emit('moved'))
                    .catch((error: AxiosError) => {
                        this.error = `There was an error moving the requested ${(this.file.directory) ? 'folder' : 'file'}. Response was: ${error.message}`;
                        console.error('Error at Server::Files::Move', {error});
                    })
                    .then(() => this.isLoading = false);
            },

            resetModal: function () {
                this.isLoading = false;
                this.moveTo = null;
                this.error = null;
            },
        }
    });
</script>
