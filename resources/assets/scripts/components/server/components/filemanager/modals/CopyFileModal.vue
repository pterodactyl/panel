<template>
    <SpinnerModal :visible="true">
        Copying {{ file.directory ? 'directory' : 'file' }}...
    </SpinnerModal>
</template>

<script lang="ts">
    import Vue from 'vue';
    import SpinnerModal from "../../../../core/SpinnerModal.vue";
    import {DirectoryContentObject} from '@/api/server/types';
    import {mapState} from "vuex";
    import {ServerState} from '@/store/types';
    import { join } from 'path';
    import {copyElement} from '@/api/server/files/copyElement';
    import {AxiosError} from "axios";

    export default Vue.extend({
        components: { SpinnerModal },

        computed: mapState('server', {
            server: (state: ServerState) => state.server,
            credentials: (state: ServerState) => state.credentials,
            fm: (state: ServerState) => state.fm,
        }),

        props: {
            file: { type: Object as () => DirectoryContentObject, required: true },
        },

        /**
         * This modal works differently than the other modals that exist for the file manager.
         * When it is mounted we will immediately show the spinner, and begin the copy operation
         * on the give file or directory. Once that operation is complete we will emit the event
         * and allow the parent to close the modal and do whatever else it thinks is needed.
         */
        mounted: function () {
            let newPath = join(this.fm.currentDirectory, `${this.file.name} copy`);

            if (!this.file.directory) {
                const extension = this.file.name.substring(this.file.name.lastIndexOf('.') + 1);

                if (extension !== this.file.name && extension.length > 0) {
                    const name = this.file.name.substring(0, this.file.name.lastIndexOf('.'));

                    newPath = join(this.fm.currentDirectory, `${name} copy.${extension}`)
                }
            }

            copyElement(this.server.uuid, this.credentials, {currentPath: join(this.fm.currentDirectory, this.file.name), newPath})
                .then(() => this.$emit('close'))
                .catch((error: AxiosError) => {
                    alert(`There was an error creating a copy of this item: ${error.message}`);
                    console.error('Error at Server::Files::Copy', {error});
                })
                .then(() => this.$emit('close'));
        },
    })
</script>
