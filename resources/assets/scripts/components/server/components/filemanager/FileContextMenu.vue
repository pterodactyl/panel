<template>
    <div class="context-menu">
        <div>
            <div class="context-row" v-on:click="triggerAction('rename')">
                <div class="icon">
                    <Icon name="edit-3"/>
                </div>
                <div class="action"><span>Rename</span></div>
            </div>
            <div class="context-row" v-on:click="triggerAction('move')">
                <div class="icon">
                    <Icon name="corner-up-left" class="h-4"/>
                </div>
                <div class="action"><span class="text-left">Move</span></div>
            </div>
            <div class="context-row" v-on:click="triggerAction('copy')">
                <div class="icon">
                    <Icon name="copy" class="h-4"/>
                </div>
                <div class="action">Copy</div>
            </div>
            <div class="context-row" v-on:click="triggerAction('download')" v-if="!object.directory">
                <div class="icon">
                    <Icon name="download" class="h-4"/>
                </div>
                <div class="action">Download</div>
            </div>
        </div>
        <div>
            <div class="context-row" v-on:click="openNewFileModal">
                <div class="icon">
                    <Icon name="file-plus" class="h-4"/>
                </div>
                <div class="action">New File</div>
            </div>
            <div class="context-row" v-on:click="openFolderModal">
                <div class="icon">
                    <Icon name="folder-plus" class="h-4"/>
                </div>
                <div class="action">New Folder</div>
            </div>
        </div>
        <div>
            <div class="context-row danger" v-on:click="triggerAction('delete')">
                <div class="icon">
                    <Icon name="delete" class="h-4"/>
                </div>
                <div class="action">Delete</div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Icon from "../../../core/Icon.vue";
    import {DirectoryContentObject} from "@/api/server/types";

    export default Vue.extend({
        name: 'FileContextMenu',
        components: {Icon},

        props: {
            object: {
                type: Object as () => DirectoryContentObject,
                required: true,
            },
        },

        methods: {
            openFolderModal: function () {
                window.events.$emit('server:files:open-directory-modal');
                this.$emit('close');
            },

            openNewFileModal: function () {
                window.events.$emit('server:files:open-new-file-modal');
                this.$emit('close');
            },

            triggerAction: function (action: string) {
                this.$emit(`action:${action}`);
            }
        }
    });
</script>
