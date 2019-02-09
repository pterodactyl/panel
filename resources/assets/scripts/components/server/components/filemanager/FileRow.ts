import Vue from 'vue';
import Icon from "../../../core/Icon";
import {Vue as VueType} from "vue/types/vue";
import { readableSize, formatDate } from '../../../../helpers'
import FileContextMenu from "./FileContextMenu";

export default Vue.component('file-row', {
    components: {
        Icon,
        FileContextMenu,
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
        /**
         * Handle a right-click action on a file manager row.
         */
        showContextMenu: function (e: MouseEvent) {
            e.preventDefault();

            // @ts-ignore
            this.$parent.$emit('collapse-menus', this._uid);

            this.contextMenuVisible = true;

            const menuWidth = (this.$refs.contextMenu as VueType).$el.offsetWidth;
            const positionElement = e.clientX - Math.round(menuWidth / 2);

            (this.$refs.contextMenu as VueType).$el.setAttribute('style', `left: ${positionElement}; top: ${e.clientY}`);
        },

        /**
         * Determine if a file can be edited on the Panel.
         */
        canEdit: function (file: any): boolean {
            return this.editable.indexOf(file.mime) >= 0;
        },

        /**
         * Handle a click anywhere in the document and hide the context menu if that click is not
         * a right click and isn't occurring somewhere in the currently visible context menu.
         *
         * @private
         */
        _clickListener: function (e: MouseEvent) {
            if (e.button !== 2 && this.contextMenuVisible) {
                if (e.target !== (this.$refs.contextMenu as VueType).$el && !(this.$refs.contextMenu as VueType).$el.contains(e.target as Node)) {
                    this.contextMenuVisible = false;
                }
            }
        },

        readableSize: readableSize,
        formatDate: formatDate,
    },

    template: `
        <div>
            <div class="row" :class="{ clickable: canEdit(file), 'active-selection': contextMenuVisible }" v-on:contextmenu="showContextMenu">
                <div class="flex-none icon">
                    <icon name="file-text" v-if="!file.symlink"/>
                    <icon name="link2" v-else/>
                </div>
                <div class="flex-1">{{file.name}}</div>
                <div class="flex-1 text-right text-neutral-600">{{readableSize(file.size)}}</div>
                <div class="flex-1 text-right text-neutral-600">{{formatDate(file.modified)}}</div>
                <div class="flex-none w-1/6"></div>
            </div>
            <file-context-menu class="context-menu" v-show="contextMenuVisible" ref="contextMenu"/>
        </div>
    `
});
