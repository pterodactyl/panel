<template>
    <div>
        <div v-if="loading">
            <div class="spinner spinner-xl blue"></div>
        </div>
        <div class="filemanager" v-else>
            <div class="header">
                <div class="flex-none w-8"></div>
                <div class="flex-1">Name</div>
                <div class="flex-1 text-right">Size</div>
                <div class="flex-1 text-right">Modified</div>
                <div class="flex-none w-1/6">Actions</div>
            </div>
            <div class="row clickable" v-for="directory in directories" v-on:click="currentDirectory = directory.name">
                <div class="flex-none icon"><folder-icon/></div>
                <div class="flex-1">{{directory.name}}</div>
                <div class="flex-1 text-right text-grey-dark"></div>
                <div class="flex-1 text-right text-grey-dark">{{formatDate(directory.modified)}}</div>
                <div class="flex-none w-1/6"></div>
            </div>
            <div class="row" v-for="file in files" :class="{ clickable: canEdit(file) }">
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
    </div>
</template>

<script>
    import filter from 'lodash/filter';
    import format from 'date-fns/format';
    import { mapState } from 'vuex';
    import { FileTextIcon, FolderIcon, Link2Icon } from 'vue-feather-icons';

    export default {
        name: 'file-manager-page',
        components: { FileTextIcon, FolderIcon, Link2Icon },

        computed: {
            ...mapState('server', ['server', 'credentials']),
        },

        watch: {
            currentDirectory: function () {
                this.listDirectory();
            },
        },

        data: function () {
            return {
                currentDirectory: '/',
                loading: true,

                directories: [],
                editableFiles: [],
                files: [],
            };
        },

        mounted: function () {
            this.listDirectory();
        },

        methods: {
            /**
             * List the contents of a directory.
             */
            listDirectory: function () {
                this.loading = true;

                window.axios.get(this.route('server.files', {
                    server: this.$route.params.id,
                    directory: this.currentDirectory,
                }))
                    .then((response) => {
                        this.files = filter(response.data.contents, function (o) {
                            return o.file;
                        });

                        this.directories = filter(response.data.contents, function (o) {
                            return o.directory;
                        });

                        this.editableFiles = response.data.editable;
                    })
                    .catch(console.error)
                    .finally(() => {
                        this.loading = false;
                    });
            },

            /**
             * Determine if a file can be edited on the Panel.
             *
             * @param {Object} file
             * @return {Boolean}
             */
            canEdit: function (file) {
                return this.editableFiles.indexOf(file.mime) >= 0;
            },

            /**
             * Return the human readable filesize for a given number of bytes. This
             * uses 1024 as the base, so the response is denoted accordingly.
             *
             * @param {Number} bytes
             * @return {String}
             */
            readableSize: function (bytes) {
                if (Math.abs(bytes) < 1024) {
                    return `${bytes} Bytes`;
                }

                let u = -1;
                const units = ['KiB', 'MiB', 'GiB', 'TiB'];

                do {
                    bytes /= 1024;
                    u++;
                } while (Math.abs(bytes) >= 1024 && u < units.length - 1);

                return `${bytes.toFixed(1)} ${units[u]}`
            },

            /**
             * Format the given date as a human readable string.
             *
             * @param {String} date
             * @return {String}
             */
            formatDate: function (date) {
                return format(date, 'MMM D, YYYY [at] HH:MM');
            },
        }
    };
</script>
