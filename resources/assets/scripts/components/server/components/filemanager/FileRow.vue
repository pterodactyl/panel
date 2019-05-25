<template>
    <div>
        <div v-on:contextmenu="showContextMenu">
            <div
                class="row"
                :class="{ 'cursor-pointer': canEdit(file), 'active-selection': contextMenuVisible }"
                v-if="!file.directory"
                v-on:click="openFileEditModal(file)"
            >
                <div class="flex-none icon">
                    <Icon name="file-text" v-if="!file.symlink"/>
                    <Icon name="link2" v-else/>
                </div>
                <div class="flex-1">{{file.name}}</div>
                <div class="flex-1 text-right text-neutral-600">{{readableSize(file.size)}}</div>
                <div class="flex-1 text-right text-neutral-600">{{formatDate(file.modified)}}</div>
                <div class="flex-none icon cursor-pointer" v-on:click="showContextMenu" ref="menuTriggerIcon">
                    <Icon name="more-vertical" class="text-neutral-500"/>
                </div>
            </div>
            <router-link class="row"
                         :class="{ 'active-selection': contextMenuVisible }"
                         :to="{ name: 'server-files', params: { path: getClickablePath(file.name) }}"
                         v-else
            >
                <div class="flex-none icon text-primary-700">
                    <Icon name="folder"/>
                </div>
                <div class="flex-1">{{file.name}}</div>
                <div class="flex-1 text-right text-neutral-600"></div>
                <div class="flex-1 text-right text-neutral-600">{{formatDate(file.modified)}}</div>
                <div class="flex-none icon" v-on:click="showContextMenu" ref="menuTriggerIcon">
                    <Icon name="more-vertical" class="text-neutral-500"/>
                </div>
            </router-link>
        </div>
        <FileContextMenu
            class="context-menu"
            :object="file"
            v-show="contextMenuVisible"
            v-on:close="contextMenuVisible = false"
            v-on:action:delete="showModal('delete')"
            v-on:action:rename="showModal('rename')"
            v-on:action:copy="showModal('copy')"
            v-on:action:move="showModal('move')"
            v-on:action:download="showModal('download')"
            ref="contextMenu"
        />
        <CopyFileModal :file="file" v-if="modals.copy" v-on:close="$emit('list')"/>
        <DownloadFileModal :file="file" v-if="!file.directory && modals.download" v-on:close="modals.download = false"/>
        <DeleteFileModal :visible.sync="modals.delete" :object="file" v-on:deleted="$emit('deleted')" v-on:close="modal.delete = false"/>
        <RenameModal :visible.sync="modals.rename" :object="file" v-on:renamed="$emit('list')" v-on:close="modal.rename = false"/>
        <MoveFileModal :visible.sync="modals.move" :file="file" v-on:moved="$emit('list')" v-on:close="modal.move = false"/>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Icon from "../../../core/Icon.vue";
    import {Vue as VueType} from "vue/types/vue";
    import {formatDate, readableSize} from '../../../../helpers'
    import FileContextMenu from "./FileContextMenu.vue";
    import {DirectoryContentObject} from "@/api/server/types";
    import DeleteFileModal from "@/components/server/components/filemanager/modals/DeleteFileModal.vue";
    import RenameModal from "@/components/server/components/filemanager/modals/RenameModal.vue";
    import CopyFileModal from "@/components/server/components/filemanager/modals/CopyFileModal.vue";
    import DownloadFileModal from "@/components/server/components/filemanager/modals/DownloadFileModal.vue";
    import MoveFileModal from "@/components/server/components/filemanager/modals/MoveFileModal.vue";

    type DataStructure = {
        currentDirectory: string,
        contextMenuVisible: boolean,
        modals: { [key: string]: boolean },
    };

    export default Vue.extend({
        name: 'FileRow',
        components: {CopyFileModal, DownloadFileModal, DeleteFileModal, MoveFileModal, Icon, FileContextMenu, RenameModal},

        props: {
            file: {
                type: Object as () => DirectoryContentObject,
                required: true,
            },
            editable: {
                type: Array as () => Array<string>,
                default: () => [],
                required: false,
            },
        },

        data: function (): DataStructure {
            return {
                currentDirectory: this.$route.params.path || '/',
                contextMenuVisible: false,
                modals: {
                    rename: false,
                    delete: false,
                    copy: false,
                    move: false,
                    download: false,
                },
            };
        },

        mounted: function () {
            document.addEventListener('click', this._clickListener);

            // If the parent component emits the collapse menu event check if the unique ID of the component
            // is this one. If not, collapse the menu (we right clicked into another element).
            this.$parent.$on('collapse-menus', (uid: string) => {
                // @ts-ignore
                if (this._uid !== uid) {
                    this.contextMenuVisible = false;
                }
            })
        },

        beforeDestroy: function () {
            document.removeEventListener('click', this._clickListener, false);
        },

        methods: {
            showModal: function (name: string) {
                this.contextMenuVisible = false;

                Object.keys(this.modals).forEach(k => {
                    this.modals[k] = k === name;
                });
            },

            /**
             * Handle a right-click action on a file manager row.
             */
            showContextMenu: function (e: MouseEvent) {
                e.preventDefault();

                // @ts-ignore
                this.$parent.$emit('collapse-menus', this._uid);
                this.contextMenuVisible = true;

                this.$nextTick(() => {
                    const menuWidth = (this.$refs.contextMenu as VueType).$el.clientWidth;
                    const positionElement = e.clientX - Math.round(menuWidth / 2);

                    (this.$refs.contextMenu as VueType).$el.setAttribute('style', `left: ${positionElement}px; top: ${e.layerY}px`);
                });
            },

            openFileEditModal: function (file: DirectoryContentObject) {
                if (!file.directory && this.canEdit(file)) {
                    window.events.$emit('server:files:open-edit-file-modal', file);
                }
            },

            /**
             * Determine if a file can be edited on the Panel.
             */
            canEdit: function (file: DirectoryContentObject): boolean {
                return !file.directory && this.editable.indexOf(file.mime) >= 0;
            },

            /**
             * Handle a click anywhere in the document and hide the context menu if that click is not
             * a right click and isn't occurring somewhere in the currently visible context menu.
             *
             * @private
             */
            _clickListener: function (e: MouseEvent) {
                if (e.button !== 2 && this.contextMenuVisible) {
                    // If we're clicking the trigger icon don't discard the event.
                    if (this.$refs.menuTriggerIcon) {
                        if (e.target === this.$refs.menuTriggerIcon || (this.$refs.menuTriggerIcon as HTMLDivElement).contains(e.target as Node)) {
                            return;
                        }
                    }

                    // If the target is outside the scope of the context menu, hide it.
                    if (e.target !== (this.$refs.contextMenu as VueType).$el && !(this.$refs.contextMenu as VueType).$el.contains(e.target as Node)) {
                        this.contextMenuVisible = false;
                    }
                }
            },

            getClickablePath(directory: string): string {
                return `${this.currentDirectory.replace(/\/$/, '')}/${directory}`;
            },

            readableSize: readableSize,
            formatDate: formatDate,
        },
    });
</script>
