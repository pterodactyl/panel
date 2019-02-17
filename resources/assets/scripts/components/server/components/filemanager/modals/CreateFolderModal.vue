<template>
    <Modal :show="visible" v-on:close="onModalClose" :showCloseIcon="false">
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
                <button class="btn btn-primary btn-sm" type="submit" v-on:submit.prevent="submit">
                    Create
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

    export default Vue.extend({
        name: 'CreateFolderModal',
        components: {Modal},

        computed: {
            ...mapState('server', ['fm']),
        },

        data: function () {
            return {
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
                    (this.$refs.folderNameField as HTMLInputElement).focus();
                });
            });
        },

        methods: {
            submit: function () {
                this.$validator.validate().then((result) => {
                    if (!result) {
                        return;
                    }

                    this.onModalClose();
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
