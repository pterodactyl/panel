<template>
    <SpinnerModal :visible="true">
        Downloading {{ file.name }}...
    </SpinnerModal>
</template>

<script lang="ts">
    import Vue from 'vue';
    import SpinnerModal from "../../../../core/SpinnerModal.vue";
    import {DirectoryContentObject} from '@/api/server/types';
    import {mapState} from "vuex";
    import {ServerState} from '@/store/types';
    import { join } from 'path';
    import {AxiosError} from "axios";
    import {getDownloadToken} from '@/api/server/files/getDownloadToken';

    export default Vue.extend({
        components: { SpinnerModal },

        computed: mapState('server', {
            credentials: (state: ServerState) => state.credentials,
            fm: (state: ServerState) => state.fm,
        }),

        props: {
            file: { type: Object as () => DirectoryContentObject, required: true },
        },

        /**
         * This modal works differently than the other modals that exist for the file manager.
         * When it is mounted we will immediately show the spinner, and then begin the operation
         * to get the download token and redirect the user to that new URL.
         */
        mounted: function () {
            const path = join(this.fm.currentDirectory, this.file.name);

            getDownloadToken(this.$route.params.id, path)
                .then((token) => {
                    if (token) {
                        window.location.href = `${this.credentials.node}/v1/server/file/download/${token}`;
                    }
                })
                .catch((error: AxiosError) => {
                    alert(`There was an error trying to download this ${this.file.directory ? 'folder' : 'file'}: ${error.message}`);
                    console.error('Error at Server::Files::Download', {error});
                })
                .then(() => this.$emit('close'));
        },
    })
</script>
