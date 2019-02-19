<template>
    <Modal :show="visible" v-on:close="visible = false">
        <div v-if="object">
            <h3 class="font-medium mb-6">Really delete {{ object.name }}?</h3>
            <p class="text-sm text-neutral-700">
                Deletion is a permanent operation: <strong>{{ object.name }}</strong><span v-if="object.folder">, as well as its contents,</span> will be removed immediately.
            </p>
            <div class="mt-8 text-right">
                <button class="btn btn-secondary btn-sm" v-on:click.prevent="visible = false">Cancel</button>
                <button class="btn btn-red btn-sm ml-2">Yes, Delete</button>
            </div>
        </div>
    </Modal>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Modal from '@/components/core/Modal.vue';
    import {DirectoryContentObject} from "@/api/server/types";

    type DataStructure = {
        object: null | DirectoryContentObject,
        visible: boolean,
    };

    export default Vue.extend({
        name: 'DeleteFileModal',
        components: {Modal},

        data: function (): DataStructure {
            return {
                visible: false,
                object: null,
            };
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
    });
</script>
