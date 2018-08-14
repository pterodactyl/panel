<template>
    <div>
        <div class="filemanager-breadcrumbs">
            /<span class="px-1">home</span><!--
            -->/<router-link :to="{ name: 'server-files' }" class="px-1">container</router-link><!--
            --><span v-for="crumb in breadcrumbs" class="inline-block">
                <span v-if="crumb.path">
                    /<router-link :to="{ name: 'server-files', params: { path: crumb.path } }" class="px-1">{{crumb.directoryName}}</router-link>
                </span>
                <span v-else>
                    /<span class="px-1 font-semibold">{{crumb.directoryName}}</span>
                </span>
            </span>
        </div>
        <div v-if="loading">
            <div class="spinner spinner-xl blue"></div>
        </div>
        <div v-else-if="!loading && errorMessage">
            <div class="alert error" v-text="errorMessage"></div>
        </div>
        <div class="filemanager" v-else>
            <div class="header">
                <div class="flex-none w-8"></div>
                <div class="flex-1">Name</div>
                <div class="flex-1 text-right">Size</div>
                <div class="flex-1 text-right">Modified</div>
                <div class="flex-none w-1/6">Actions</div>
            </div>
            <div v-if="!directories.length && !files.length">
                <p class="text-grey text-sm text-center p-6 pb-4">This directory is empty.</p>
            </div>
            <div v-else>
                <router-link class="row clickable"
                             v-for="directory in directories"
                             :to="{ name: 'server-files', params: { path: getClickablePath(directory.name).replace(/^\//, '') }}"
                             :key="directory.name + directory.modified"
                >
                    <div class="flex-none icon">
                        <folder-icon/>
                    </div>
                    <div class="flex-1">{{directory.name}}</div>
                    <div class="flex-1 text-right text-grey-dark"></div>
                    <div class="flex-1 text-right text-grey-dark">{{formatDate(directory.modified)}}</div>
                    <div class="flex-none w-1/6"></div>
                </router-link>
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
    </div>
</template>

<script>
    import _ from 'lodash';
    import filter from 'lodash/filter';
    import isObject from 'lodash/isObject';
    import format from 'date-fns/format';
    import { mapState } from 'vuex';
    import { FileTextIcon, FolderIcon, Link2Icon } from 'vue-feather-icons';

    export default {
        name: 'file-manager-page',
        components: {FileTextIcon, FolderIcon, Link2Icon},

        computed: {
            ...mapState('server', ['server', 'credentials']),
            ...mapState('socket', ['connected']),

            /**
             * Configure the breadcrumbs that display on the filemanager based on the directory that the
             * user is currently in.
             */
            breadcrumbs: function () {
                const directories = this.currentDirectory.replace(/^\/|\/$/, '').split('/');
                if (directories.length < 1 || !directories[0]) {
                    return [];
                }

                return _.map(directories, function (value, key) {
                    if (key === directories.length - 1) {
                        return {directoryName: value};
                    }

                    return {
                        directoryName: value,
                        path: directories.slice(0, key + 1).join('/'),
                    };
                });
            }
        },

        watch: {
            /**
             * When the route changes reload the directory.
             */
            '$route': function (to) {
                this.currentDirectory = to.params.path || '/';
            },

            /**
             * Watch the current directory setting and when it changes update the file listing.
             */
            currentDirectory: function () {
                this.listDirectory();
            },

            /**
             * When we reconnect to the Daemon make sure we grab a listing of all of the files
             * so that the error message disappears and we then load in a fresh listing.
             */
            connected: function () {
                if (this.connected) {
                    this.listDirectory();
                }
            }
        },

        data: function () {
            return {
                currentDirectory: this.$route.params.path || '/',
                loading: true,
                errorMessage: null,

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
                    directory: encodeURI(this.currentDirectory.replace(/^\/|\/$/, '')),
                }))
                    .then((response) => {
                        this.files = filter(response.data.contents, function (o) {
                            return o.file;
                        });

                        this.directories = filter(response.data.contents, function (o) {
                            return o.directory;
                        });

                        this.editableFiles = response.data.editable;
                        this.errorMessage = null;
                    })
                    .catch(err => {
                        console.error({err});
                        if (err.response.status === 404) {
                            this.errorMessage = 'The directory you requested could not be located on the server.';
                            return;
                        }

                        if (err.response.data && isObject(err.response.data.errors)) {
                            err.response.data.errors.forEach(error => {
                                this.errorMessage = error.detail;
                            });
                        }
                    })
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
             * Return a formatted directory path that is used to switch to a nested directory.
             *
             * @return {String}
             */
            getClickablePath (directory) {
                return `${this.currentDirectory.replace(/\/$/, '')}/${directory}`;
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

                return `${bytes.toFixed(1)} ${units[u]}`;
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
