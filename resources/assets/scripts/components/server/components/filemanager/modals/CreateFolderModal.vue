<template>
    <Modal :show="visible" v-on:close="onModalClose" :showCloseIcon="false" :dismissable="!isLoading">
        <div class="flex items-end">
            <div class="flex-1">
                <label class="input-label">
                    Folder Name
                </label>
                <input
                        type="text" class="input" name="folder_name"
                        ref="folderNameField"
                        v-model="folderName"
                        v-validate.disabled="'required'"
                        v-validate="'alpha_dash'"
                        data-vv-as="Folder Name"
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
                        Create
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
    import Modal from '@/components/core/Modal.vue';
    import {mapState} from "vuex";
    import {createFolder} from "@/api/server/files/createFolder";

    export default Vue.extend({
        name: 'CreateFolderModal',
        components: {Modal},

        computed: {
            ...mapState('server', ['server', 'credentials', 'fm']),
        },

        data: function () {
            return {
                isLoading: false,
                visible: false,
                folderName: '',
            };
        },

        mounted: function () {
            /**
             * When we mark the modal as visible, focus the user into the input field on the next
             * tick operation so that they can begin typing right away.
             */
            window.events.$on('server:files:open-directory-modal', () => {
                this.visible = true;
                this.$nextTick(() => {
                    if (this.$refs.folderNameField) {
                        (this.$refs.folderNameField as HTMLInputElement).focus();
                    }
                });
            });
        },

        beforeDestroy: function () {
            window.events.$off('server:files:open-directory-modal');
        },

        methods: {
            submit: function () {
                this.$validator.validate().then((result) => {
                    if (!result) {
                        return;
                    }

                    this.isLoading = true;
                    createFolder(this.server, this.credentials, `${this.fm.currentDirectory}/${this.folderName.replace(/^\//, '')}`)
                        .then(() => {
                            this.$emit('close');
                            this.onModalClose();
                        })
                        .catch(console.error.bind(this))
                        .then(() => this.isLoading = false)
                });
            },

            onModalClose: function () {
                this.visible = false;
                this.folderName = '';
                this.$validator.reset();
            },
        }
    });
</script>
