import Vue from 'vue';
import {mapState} from "vuex";
import { map } from 'lodash';
import getDirectoryContents from "@/api/server/getDirectoryContents";
import FileRow from "@/components/server/components/filemanager/FileRow";
import FolderRow from "@/components/server/components/filemanager/FolderRow";

type DataStructure = {
    loading: boolean,
    errorMessage: string | null,
    currentDirectory: string,
    files: Array<any>,
    directories: Array<any>,
    editableFiles: Array<string>,
}

export default Vue.component('file-manager', {
    components: { FileRow, FolderRow },
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

            return map(directories, function (value: string, key: number) {
                if (key === directories.length - 1) {
                    return { directoryName: value };
                }

                return {
                    directoryName: value,
                    path: directories.slice(0, key + 1).join('/'),
                };
            });
        },
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
            // @ts-ignore
            if (this.connected) {
                this.listDirectory();
            }
        },
    },

    data: function (): DataStructure {
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

            const directory = encodeURI(this.currentDirectory.replace(/^\/|\/$/, ''));
            getDirectoryContents(this.$route.params.id, directory)
                .then((response) => {
                    this.files = response.files;
                    this.directories = response.directories;
                    this.editableFiles = response.editable;
                    this.errorMessage = null;
                })
                .catch((err) => {
                    if (typeof err === 'string') {
                        this.errorMessage = err;
                        return;
                    }

                    console.error('An error was encountered while processing this request.', { err });
                })
                .then(() => {
                    this.loading = false;
                });
        },
    },

    template: `
        <div class="animate fadein">
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
            <div class="content-box">
                <div v-if="loading">
                    <div class="spinner spinner-xl blue"></div>
                </div>
                <div v-else-if="!loading && errorMessage">
                    <div class="alert error" v-text="errorMessage"></div>
                </div>
                <div v-else-if="!directories.length && !files.length">
                    <p class="textneutral-500stext-sm text-center p-6 pb-4">This directory is empty.</p>
                </div>
                <div class="filemanager" v-else>
                    <div class="header">
                        <div class="flex-none w-8"></div>
                        <div class="flex-1">Name</div>
                        <div class="flex-1 text-right">Size</div>
                        <div class="flex-1 text-right">Modified</div>
                        <div class="flex-none w-1/6">Actions</div>
                    </div>
                    <div v-for="directory in directories">
                        <folder-row :directory="directory"/>
                    </div>
                    <div v-for="file in files">
                        <file-row :file="file" :editable="editableFiles" />
                    </div>
                </div>
            </div>
        </div>
    `,
});
