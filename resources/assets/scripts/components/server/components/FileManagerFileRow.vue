<template>
    <div>
        <div class="row" :class="{ clickable: canEdit(file) }" v-on:contextmenu="showContextMenu">
            <div class="flex-none icon">
                <file-text-icon v-if="!file.symlink"/>
                <link2-icon v-else/>
            </div>
            <div class="flex-1">{{file.name}}</div>
            <div class="flex-1 text-right text-grey-dark">{{readableSize(file.size)}}</div>
            <div class="flex-1 text-right text-grey-dark">{{formatDate(file.modified)}}</div>
            <div class="flex-none w-1/6"></div>
        </div>
        <div class="context-menu" v-show="contextMenuVisible" ref="contextMenu">
            <div>
                <div class="context-row">
                    <div class="icon"><edit3-icon/></div>
                    <div class="action"><span>Rename</span></div>
                </div>
                <div class="context-row">
                    <div class="icon"><corner-up-left-icon class="h-4"/></div>
                    <div class="action"><span class="text-left">Move</span></div>
                </div>
                <div class="context-row">
                    <div class="icon"><copy-icon class="h-4"/></div>
                    <div class="action">Copy</div>
                </div>
            </div>
            <div>
                <div class="context-row">
                    <div class="icon"><file-plus-icon class="h-4"/></div>
                    <div class="action">New File</div>
                </div>
                <div class="context-row">
                    <div class="icon"><folder-plus-icon class="h-4"/></div>
                    <div class="action">New Folder</div>
                </div>
            </div>
            <div>
                <div class="context-row">
                    <div class="icon"><download-icon class="h-4"/></div>
                    <div class="action">Download</div>
                </div>
                <div class="context-row danger">
                    <div class="icon"><delete-icon class="h-4"/></div>
                    <div class="action">Delete</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { CopyIcon, CornerUpLeftIcon, DeleteIcon, DownloadIcon, Edit3Icon, FileTextIcon, FilePlusIcon, FolderPlusIcon, Link2Icon } from 'vue-feather-icons';
    import * as Helpers from './../../../helpers/index';

    export default {
        name: 'file-manager-file-row',
        components: {
            CopyIcon, CornerUpLeftIcon, DeleteIcon, DownloadIcon,
            Edit3Icon, FileTextIcon, FilePlusIcon, FolderPlusIcon,
            Link2Icon,
        },
        props: {
            file: {type: Object, required: true},
            editable: {type: Array, required: true}
        },

        data: function () {
            return {
                listener: null,
                contextMenuVisible: false,
            };
        },

        mounted: function () {
            // Handle a click anywhere in the document and hide the context menu if that click is not
            // a right click and isn't occurring somewhere in the currently visible context menu.
            this.listener = document.addEventListener('click', (e) => {
                if (e.button !== 2
                    && this.contextMenuVisible
                    && e.target !== this.$refs.contextMenu
                    && !this.$refs.contextMenu.contains(e.target)
                ) {
                    this.contextMenuVisible = false;
                }
            });

            // If the parent component emits the collapse menu event check if the unique ID of the component
            // is this one. If not, collapse the menu (we right clicked into another element).
            this.$parent.$on('collapse-menus', (uid) => {
                if (this._uid !== uid) {
                    this.contextMenuVisible = false;
                }
            })
        },

        beforeDestroy: function () {
            document.removeEventListener('click', this.listener);
        },

        methods: {
            /**
             * Handle a right-click action on a file manager row.
             *
             * @param {Event} e
             */
            showContextMenu: function (e) {
                e.preventDefault();
                this.$parent.$emit('collapse-menus', this._uid);
                this.contextMenuVisible = !this.contextMenuVisible;
            },

            /**
             * Determine if a file can be edited on the Panel.
             *
             * @param {Object} file
             * @return {Boolean}
             */
            canEdit: function (file) {
                return this.editable.indexOf(file.mime) >= 0;
            },

            readableSize: Helpers.readableSize,
            formatDate: Helpers.formatDate,
        }
    };
</script>
