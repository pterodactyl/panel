<template>
    <transition name="modal">
        <div class="modal-mask" v-show="isVisible">
            <div class="modal-container full-screen" @click.stop>
                <div class="modal-close-icon" v-on:click="isVisible = false">
                    <Icon name="x" aria-label="Close modal" role="button"/>
                </div>
                <MessageBox class="alert error mb-8" title="Error" :message="error" v-if="error"/>
                <div id="editor" class="h-full"></div>
            </div>
        </div>
    </transition>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Icon from "@/components/core/Icon.vue";
    import MessageBox from "@/components/MessageBox.vue";
    import {ApplicationState} from '@/store/types';
    import {mapState} from "vuex";
    // @ts-ignore
    import CodeFlask from "codeflask";

    export default Vue.extend({
        name: 'NewFileModal',

        components: {Icon, MessageBox},

        data: function (): { error: string | null, isVisible: boolean, isLoading: boolean } {
            return {
                error: null,
                isVisible: false,
                isLoading: false,
            };
        },

        computed: mapState({
            fm: (state: ApplicationState) => state.server.fm,
        }),

        mounted: function () {
            window.events.$on('server:files:open-new-file-modal', () => {
                this.isVisible = true;

                this.$nextTick(() => {
                    const flask = new CodeFlask('#editor', {
                        language: 'js',
                        lineNumbers: true,
                    });

                    flask.updateCode('');
                })
            });
        },

        methods: {
            submit: function () {

            },
        }
    })
</script>

<style>
    #editor > .codeflask {
        @apply .rounded;
    }
</style>
