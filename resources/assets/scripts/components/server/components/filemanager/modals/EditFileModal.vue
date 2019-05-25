<template>
    <transition name="modal">
        <div class="modal-mask" v-show="isVisible">
            <div class="modal-container full-screen" @click.stop>
                <div class="modal-close-icon" v-on:click="isVisible = false">
                    <Icon name="x" aria-label="Close modal" role="button"/>
                </div>
                <MessageBox class="alert error mb-8" title="Error" :message="error" v-if="error"/>
                <div id="editor"></div>
                <div class="flex mt-4 bg-white rounded p-2">
                    <div class="flex-1">
                        <select v-on:change="updateFileLanguage">
                            <option v-for="item in supportedTypes" :value="item.type">{{ item.name }}</option>
                        </select>
                    </div>
                    <button class="btn btn-secondary btn-sm" v-on:click="isVisible = false">
                        Cancel
                    </button>
                    <button class="ml-2 btn btn-primary btn-sm">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Icon from "@/components/core/Icon.vue";
    import MessageBox from "@/components/MessageBox.vue";
    import {ApplicationState, FileManagerState} from '@/store/types';
    import {mapState} from "vuex";
    import * as Ace from 'brace';
    import { join } from 'path';
    import {DirectoryContentObject} from "@/api/server/types";
    import getFileContents from '@/api/server/files/getFileContents';

    interface Data {
        file?: DirectoryContentObject,
        serverUuid?: string,
        fm?: FileManagerState,
        error: string | null,
        editor: Ace.Editor | null,
        isVisible: boolean,
        isLoading: boolean,
        supportedTypes: {type: string, name: string}[],
    }

    export default Vue.extend({
        name: 'NewFileModal',

        components: {Icon, MessageBox},

        data: function (): Data {
            return {
                error: null,
                editor: null,
                isVisible: false,
                isLoading: false,
                supportedTypes: [
                    {type: 'dockerfile', name: 'Docker'},
                    {type: 'golang', name: 'Go'},
                    {type: 'html', name: 'HTML'},
                    {type: 'java', name: 'Java'},
                    {type: 'javascript', name: 'Javascript'},
                    {type: 'json', name: 'JSON'},
                    {type: 'kotlin', name: 'Kotlin'},
                    {type: 'lua', name: 'Lua'},
                    {type: 'markdown', name: 'Markdown'},
                    {type: 'plain_text', name: 'Text'},
                    {type: 'php', name: 'PHP'},
                    {type: 'properties', name: 'Properties'},
                    {type: 'python', name: 'Python'},
                    {type: 'ruby', name: 'Ruby'},
                    {type: 'sh', name: 'Shell'},
                    {type: 'sql', name: 'SQL'},
                    {type: 'xml', name: 'XML'},
                    {type: 'yaml', name: 'YAML'},
                ],
            };
        },

        computed: mapState({
            fm: (state: ApplicationState) => state.server.fm,
            serverUuid: (state: ApplicationState) => state.server.server.uuid,
        }),

        mounted: function () {
            window.events.$on('server:files:open-edit-file-modal', (file?: DirectoryContentObject) => {
                this.file = file;
                this.isVisible = true;

                this.$nextTick(() => {
                    this.editor = Ace.edit('editor');
                    this.loadDependencies()
                        .then(() => this.loadLanguages())
                        .then(() => this.configureEditor())
                        .then(() => this.loadFileContent())
                });
            });
        },

        methods: {
            submit: function () {

            },

            loadFileContent: function () {
                if (!this.file || !this.editor || this.file.directory) {
                    return;
                }

                getFileContents(this.serverUuid!, join(this.fm!.currentDirectory, this.file.name))
                    .then(contents => {
                        this.editor!.$blockScrolling = Infinity;
                        this.editor!.setValue(contents, 1);
                    });
            },

            updateFileLanguage: function (e: MouseEvent) {
                if (!this.editor) {
                    return;
                }

                this.editor.getSession().setMode(`ace/mode/${(<HTMLSelectElement>e.target).value}`);
            },

            loadLanguages: function (): Promise<any[]> {
                return Promise.all(
                    this.supportedTypes.map(o => import(
                        /* webpackChunkName: "ace_editor" */
                        /* webpackMode: "lazy-once" */
                        /* webpackInclude: /(dockerfile|golang|html|java|javascript|json|kotlin|lua|markdown|plain_text|php|properties|python|ruby|sh|sql|xml|yaml).js$/ */
                        `brace/mode/${o.type}`
                    ))
                );
            },

            loadDependencies: function (): Promise<any[]> {
                return Promise.all([
                    // @ts-ignore
                    import(/* webpackChunkName: "ace_editor" */ 'brace/ext/whitespace'),
                    // @ts-ignore
                    import(/* webpackChunkName: "ace_editor" */ 'brace/ext/modelist'),
                    // @ts-ignore
                    import(/* webpackChunkName: "ace_editor" */ 'brace/theme/chrome'),
                ]);
            },

            configureEditor: function () {
                if (!this.editor) {
                    return;
                }

                // const modelist = Ace.acequire('brace/ext/whitespace');
                const whitespace = Ace.acequire('ace/ext/whitespace');

                this.editor.setTheme('ace/theme/chrome');
                this.editor.setOptions({
                    fontFamily: '"SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier, monospace',
                });
                this.editor.getSession().setUseWrapMode(true);
                this.editor.setShowPrintMargin(true);

                whitespace.commands.forEach((c: Ace.EditorCommand) => {
                    this.editor!.commands.addCommand(c);
                });
                whitespace.detectIndentation(this.editor.session);
            }
        }
    })
</script>

<style>
    #editor {
        @apply .h-full .relative;

        & > .ace_gutter > .ace_layer, & > .ace_scroller {
            @apply .py-1;
        }

        & .ace_gutter-active-line {
            @apply .mt-1;
        }
    }

    .ace_editor {
        @apply .rounded .p-1;
    }
</style>
