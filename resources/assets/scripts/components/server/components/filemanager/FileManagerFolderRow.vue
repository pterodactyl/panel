<template>
    <div>
        <router-link class="row clickable"
                     :to="{ name: 'server-files', params: { path: getClickablePath(directory.name).replace(/^\//, '') }}"
        >
            <div class="flex-none icon">
                <folder-icon/>
            </div>
            <div class="flex-1">{{directory.name}}</div>
            <div class="flex-1 text-right text-grey-dark"></div>
            <div class="flex-1 text-right text-grey-dark">{{formatDate(directory.modified)}}</div>
            <div class="flex-none w-1/6"></div>
        </router-link>
    </div>
</template>

<script>
    import { FolderIcon } from 'vue-feather-icons';
    import { formatDate } from './../../../../helpers/index';

    export default {
        name: 'file-manager-folder-row',
        components: { FolderIcon },
        props: {
            directory: {type: Object, required: true},
        },

        data: function () {
            return {
                currentDirectory: this.$route.params.path || '/',
            };
        },

        methods: {
            /**
             * Return a formatted directory path that is used to switch to a nested directory.
             *
             * @return {String}
             */
            getClickablePath (directory) {
                return `${this.currentDirectory.replace(/\/$/, '')}/${directory}`;
            },

            formatDate: formatDate,
        }
    }
</script>
