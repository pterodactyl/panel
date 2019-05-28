<template>
    <div class="animated-fade-in">
        <div class="filemanager-breadcrumbs">
            /<span class="px-1">home</span><!--
                -->/
            <router-link :to="{ name: 'server-files' }" class="px-1">container</router-link><!--
                --><span v-for="crumb in breadcrumbs" class="inline-block">
                    <span v-if="crumb.path">
                        /<router-link :to="{ name: 'server-files', params: { path: crumb.path } }" class="px-1">{{crumb.directoryName}}</router-link>
                    </span>
                    <span v-else>
                        /<span class="px-1 text-neutral-600 font-medium">{{crumb.directoryName}}</span>
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
                <p class="text-neutral-500 text-sm text-center p-6 pb-4">This directory is empty.</p>
            </div>
            <div class="filemanager animated-fade-in" v-else>
                <div class="header">
                    <div class="flex-none w-8"></div>
                    <div class="flex-1">Name</div>
                    <div class="w-1/6">Size</div>
                    <div class="w-1/5">Modified</div>
                    <div class="flex-none"></div>
                </div>
                <div v-for="file in Array.concat(directories, files)">
                    <FileRow
                            :key="file.directory ? `dir-${file.name}` : file.name"
                            :file="file"
                            :editable="editableFiles"
                            v-on:deleted="fileRowDeleted(file, file.directory)"
                            v-on:list="listDirectory"
                    />
                </div>
            </div>
        </div>
        <div class="flex mt-6" v-if="!loading && !errorMessage">
            <div class="flex-1"></div>
            <div class="mr-4">
                <a href="#" class="block btn btn-secondary btn-sm" v-on:click.prevent="openNewFolderModal">New Folder</a>
            </div>
            <div>
                <a href="#" class="block btn btn-primary btn-sm" v-on:click.prevent="openNewFileModal">New File</a>
            </div>
        </div>
        <CreateFolderModal v-on:created="directoryCreated"/>
        <EditFileModal v-on:refresh="listDirectory"/>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import { join } from 'path';
    import {map} from 'lodash';
    import getDirectoryContents from "@/api/server/getDirectoryContents";
    import FileRow from "@/components/server/components/filemanager/FileRow.vue";
    import CreateFolderModal from '../components/filemanager/modals/CreateFolderModal.vue';
    import DeleteFileModal from '../components/filemanager/modals/DeleteFileModal.vue';
    import {DirectoryContentObject} from "@/api/server/types";
    import EditFileModal from "@/components/server/components/filemanager/modals/EditFileModal.vue";

    type DataStructure = {
        loading: boolean,
        errorMessage: string | null,
        currentDirectory: string,
        files: Array<DirectoryContentObject>,
        directories: Array<DirectoryContentObject>,
        editableFiles: Array<string>,
    }

    export default Vue.extend({
        name: 'FileManager',
        components: {CreateFolderModal, DeleteFileModal, FileRow, EditFileModal},

        computed: {
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
                        return {directoryName: value};
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
                this.$store.dispatch('server/updateCurrentDirectory', `/${directory}`);

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

                        console.error('An error was encountered while processing this request.', {err});
                    })
                    .then(() => {
                        this.loading = false;
                    });
            },

            openNewFolderModal: function () {
                window.events.$emit('server:files:open-directory-modal');
            },

            openNewFileModal: function () {
                window.events.$emit('server:files:open-edit-file-modal');
            },

            fileRowDeleted: function (file: DirectoryContentObject, directory: boolean) {
                if (directory) {
                    this.directories = this.directories.filter(data => data !== file);
                } else {
                    this.files = this.files.filter(data => data !== file);
                }
            },

            directoryCreated: function (directory: string) {
                this.$router.push({ name: 'server-files', params: { path: join(this.currentDirectory, directory) }});
            },
        },
    });
</script>
