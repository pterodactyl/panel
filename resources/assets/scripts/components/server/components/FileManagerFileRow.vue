<template>
    <div>
        <div class="row" :class="{ clickable: canEdit(file) }">
            <div class="flex-none icon">
                <file-text-icon v-if="!file.symlink"/>
                <link2-icon v-else/>
            </div>
            <div class="flex-1">{{file.name}}</div>
            <div class="flex-1 text-right text-grey-dark">{{readableSize(file.size)}}</div>
            <div class="flex-1 text-right text-grey-dark">{{formatDate(file.modified)}}</div>
            <div class="flex-none w-1/6"></div>
        </div>
    </div>
</template>

<script>
    import { FileTextIcon, Link2Icon } from 'vue-feather-icons';
    import * as Helpers from './../../../helpers/index';

    export default {
        name: 'file-manager-file-row',
        components: { FileTextIcon, Link2Icon },
        props: {
            file: {type: Object, required: true},
            editable: {type: Array, required: true}
        },

        mounted: function () {

        },

        methods: {
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
