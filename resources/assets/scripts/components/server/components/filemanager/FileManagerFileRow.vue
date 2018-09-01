<template>
    <div>
        <div class="row" :class="{ clickable: canEdit(file), 'active-selection': contextMenuVisible }" v-on:contextmenu="showContextMenu">
            <div class="flex-none icon">
                <file-text-icon v-if="!file.symlink"/>
                <link2-icon v-else/>
            </div>
            <div class="flex-1">{{file.name}}</div>
            <div class="flex-1 text-right text-grey-dark">{{readableSize(file.size)}}</div>
            <div class="flex-1 text-right text-grey-dark">{{formatDate(file.modified)}}</div>
            <div class="flex-none w-1/6"></div>
        </div>
        <file-manager-context-menu class="context-menu" v-show="contextMenuVisible" ref="contextMenu"/>
    </div>
</template>

<script>
    import * as Helpers from './../../../../helpers/index';
    import { FileTextIcon, Link2Icon } from 'vue-feather-icons';
    import FileManagerContextMenu from './FileManagerContextMenu';

    export default {
        name: 'file-manager-file-row',
        components: {
            FileManagerContextMenu,
            FileTextIcon, Link2Icon
        },
        props: {
            file: {type: Object, required: true},
            editable: {type: Array, required: true}
        },

        data: function () {
            return {
                contextMenuVisible: false,
            };
        },

        mounted: function () {
            document.addEventListener('click', this._clickListener);

            // If the parent component emits the collapse menu event check if the unique ID of the component
            // is this one. If not, collapse the menu (we right clicked into another element).
            this.$parent.$on('collapse-menus', (uid) => {
                if (this._uid !== uid) {
                    this.contextMenuVisible = false;
                }
            })
        },

        beforeDestroy: function () {
            document.removeEventListener('click', this._clickListener, false);
        },

        methods: {
            /**
             * Handle a right-click action on a file manager row.
             *
             * @param {MouseEvent} e
             */
            showContextMenu: function (e) {
                e.preventDefault();
                this.$parent.$emit('collapse-menus', this._uid);

                this.contextMenuVisible = true;

                const menuWidth = this.$refs.contextMenu.$el.offsetWidth;
                const positionElement = e.clientX - Math.round(menuWidth / 2);

                this.$refs.contextMenu.$el.style = `left: ${positionElement}; top: ${e.clientY}`;
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

            /**
             * Handle a click anywhere in the document and hide the context menu if that click is not
             * a right click and isn't occurring somewhere in the currently visible context menu.
             *
             * @param {MouseEvent} e
             * @private
             */
            _clickListener: function (e) {
                if (e.button !== 2 && this.contextMenuVisible) {
                    if (e.target !== this.$refs.contextMenu.$el && !this.$refs.contextMenu.$el.contains(e.target)) {
                        this.contextMenuVisible = false;
                    }
                }
            },

            readableSize: Helpers.readableSize,
            formatDate: Helpers.formatDate,
        }
    };
</script>
