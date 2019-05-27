<template>
    <transition name="modal">
        <div class="modal-mask" v-show="isVisible">
            <div class="modal-container full-screen" @click.stop>
                <SpinnerModal :visible="isVisible && isLoading"/>
                <div class="modal-close-icon" v-on:click="closeModal">
                    <Icon name="x" aria-label="Close modal" role="button"/>
                </div>
                <MessageBox class="alert error mb-4" title="Error" :message="error" v-if="error"/>
                <div class="flex items-center mb-4 bg-white rounded p-2">
                        <div class="mx-2">
                            <label class="input-label mb-0" for="file-name-input">File name:</label>
                        </div>
                    <div class="flex-1">
                        <input
                            type="text"
                            name="file_name"
                            class="input"
                            id="file-name-input"
                            :disabled="typeof file !== 'undefined'"
                            v-model="fileName"
                            v-validate="'required'"
                        />
                        <p class="input-help error" v-show="errors.has('file_name')">{{ errors.first('file_name') }}</p>
                    </div>
                </div>
                <div id="editor"></div>
                <div class="flex mt-4 bg-white rounded p-2">
                    <div class="flex-1">
                        <select v-on:change="updateFileLanguage" ref="fileLanguageSelector">
                            <option v-for="item in supportedTypes" :value="item.type">
                                {{ item.name }}
                            </option>
                        </select>
                    </div>
                    <button class="btn btn-secondary btn-sm" v-on:click="closeModal">
                        Cancel
                    </button>
                    <button class="ml-2 btn btn-primary btn-sm" v-on:click="submit">
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
    import {join} from 'path';
    import {DirectoryContentObject} from "@/api/server/types";
    import getFileContents from '@/api/server/files/getFileContents';
    import SpinnerModal from "@/components/core/SpinnerModal.vue";
    import writeFileContents from '@/api/server/files/writeFileContents';
    import {httpErrorToHuman} from '@/api/http';

    interface Data {
        file?: DirectoryContentObject,
        serverUuid?: string,
        fm?: FileManagerState,
        fileName?: string,
        error: string | null,
        editor: Ace.Editor | null,
        isVisible: boolean,
        isLoading: boolean,
        supportedTypes: { type: string, name: string, default?: boolean }[],
    }

    const defaults = {
        error: null,
        editor: null,
        isVisible: false,
        isLoading: true,
        file: undefined,
        fileName: undefined,
    };

    export default Vue.extend({
        name: 'NewFileModal',

        components: {Icon, SpinnerModal, MessageBox},

        data: function (): Data {
            return {
                ...defaults,
                supportedTypes: [
                    {type: 'text', name: 'Text'},
                    {type: 'dockerfile', name: 'Docker'},
                    {type: 'golang', name: 'Go'},
                    {type: 'html', name: 'HTML'},
                    {type: 'java', name: 'Java'},
                    {type: 'javascript', name: 'Javascript'},
                    {type: 'json', name: 'JSON'},
                    {type: 'kotlin', name: 'Kotlin'},
                    {type: 'lua', name: 'Lua'},
                    {type: 'markdown', name: 'Markdown'},
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
                this.isLoading = true;
                this.fileName = file ? file.name : undefined;
                this.errors.clear();

                this.$nextTick(() => {
                    this.editor = Ace.edit('editor');
                    this.loadDependencies()
                        .then(() => this.loadLanguages())
                        .then(() => this.configureEditor())
                        .then(() => this.loadFileContent())
                        .then(() => {
                            this.isLoading = false;
                        })
                        .catch(error => {
                            console.error(error);
                            this.isLoading = false;
                            this.error = error.message;
                        });
                });
            });
        },

        watch: {
            fileName: function (newValue?: string, oldValue?: string) {
                if (newValue === oldValue || !newValue) {
                    return;
                }

                this.updateFileLanguageFromName(newValue);
            },
        },

        methods: {
            submit: function () {
                if (!this.file && (!this.fileName || this.fileName.length === 0)) {
                    this.error = 'You must provide a file name before saving.';
                    return;
                }

                this.isLoading = true;
                const content = this.editor!.getValue();

                writeFileContents(this.serverUuid!, join(this.fm!.currentDirectory, this.fileName!), content)
                    .then(() => {
                        this.error = null;

                        // @todo come up with a more graceful solution here
                        if (!this.file) {
                            this.$emit('refresh');
                            this.closeModal();
                        }
                    })
                    .catch(error => {
                        console.log(error);
                        this.error = httpErrorToHuman(error);
                    })
                    .then(() => this.isLoading = false);
            },

            loadFileContent: function (): Promise<void> {
                return new Promise((resolve, reject) => {
                    const {editor, file} = this;

                    if (!file || !editor || file.directory) {
                        return resolve();
                    }

                    getFileContents(this.serverUuid!, join(this.fm!.currentDirectory, file.name))
                        .then(contents => {
                            editor.$blockScrolling = Infinity;
                            editor.setValue(contents, 1);
                        })
                        .then(() => this.updateFileLanguageFromName(file.name))
                        .then(() => resolve())
                        .catch(reject);
                });
            },

            updateFileLanguageFromName: function (name: string) {
                const modelist = Ace.acequire('ace/ext/modelist');
                if (!modelist || !this.editor) {
                    return;
                }

                const mode = modelist.getModeForPath(name).mode || 'ace/mode/text';

                const parts = mode.split('/');
                const element = (this.$refs.fileLanguageSelector as HTMLSelectElement | null);

                if (element) {
                    const index = this.supportedTypes.findIndex(value => value.type === parts[parts.length - 1]);
                    if (index >= 0) {
                        element.selectedIndex = index;
                        this.editor.getSession().setMode(mode);
                    }
                }
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
                        /* webpackInclude: /(dockerfile|golang|html|java|javascript|json|kotlin|lua|markdown|text|php|properties|python|ruby|sh|sql|xml|yaml).js$/ */
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
            },

            closeModal: function () {
                if (this.editor) {
                    this.editor.setValue('', -1);
                }

                Object.assign(this.$data, defaults);
            },
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
