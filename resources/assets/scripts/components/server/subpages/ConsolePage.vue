<template>
    <div>
        <div class="text-xs font-mono">
            <div class="rounded-t p-2 bg-black overflow-scroll w-full" style="min-height: 16rem;max-height:64rem;">
                <div v-if="loadingConsole">
                    <div class="spinner spinner-xl mt-24"></div>
                </div>
                <div class="mb-2 text-grey-light" ref="terminal"></div>
            </div>
            <div class="rounded-b bg-grey-darkest text-white flex">
                <div class="flex-no-shrink p-2">
                    <span class="font-bold">$</span>
                </div>
                <div class="w-full">
                    <input type="text" aria-label="Send console command" class="bg-transparent text-white p-2 pl-0 w-full" placeholder="enter command and press enter to send"
                           ref="command"
                           v-model="command"
                           v-on:keyup.enter="sendCommand"
                           v-on:keydown="handleArrowKey"
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { Terminal } from 'xterm';
    import * as TerminalFit from 'xterm/lib/addons/fit/fit';
    import Status from './../../../helpers/statuses';

    Terminal.applyAddon(TerminalFit);

    export default {
        name: 'console-page',

        /**
         * Mount the component and setup all of the terminal actions. Also fetches the initial
         * logs from the server to populate into the terminal.
         */
        mounted: function () {
            this.$parent.$on('socket::connected', () => {
                this.terminal.open(this.$refs.terminal);
                this.terminal.fit();
                this.terminal.clear();

                this.$parent.$emit('send-initial-log');
            });

            this.$parent.$on('console', data => {
                this.loadingConsole = false;
                this.terminal.writeln(data);
            });

            this.$parent.$on('socket::status', s => {
                if (s === Status.STATUS_OFF) {
                    this.loadingConsole = false;
                }
            });
        },

        data: function () {
            return {
                terminal: new Terminal({
                    disableStdin: true,
                    cursorStyle: 'underline',
                    allowTransparency: true,
                    fontSize: 12,
                    fontFamily: 'Menlo,Monaco,Consolas,monospace',
                    rows: 30,
                    theme: {
                        background: 'transparent',
                        cursor: 'transparent',
                    }
                }),
                command: '',
                commandHistory: [],
                commandHistoryIndex: -1,
                loadingConsole: true,
            };
        },

        methods: {
            /**
             * Send a command to the server using the configured websocket.
             */
            sendCommand: function () {
                this.commandHistoryIndex = -1;
                this.commandHistory.unshift(this.command);
                this.$parent.$emit('send-command', this.command);
                this.command = '';
            },

            /**
             * Handle a user pressing up/down arrows when in the command field to scroll through thier
             * command history for this server.
             *
             * @param {KeyboardEvent} e
             */
            handleArrowKey: function (e) {
                if (['ArrowUp', 'ArrowDown'].indexOf(e.key) < 0 || e.key === 'ArrowDown' && this.commandHistoryIndex < 0) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                if (e.key === 'ArrowUp' && (this.commandHistoryIndex + 1 > (this.commandHistory.length - 1))) {
                    return;
                }

                this.commandHistoryIndex += (e.key === 'ArrowUp') ? 1 : -1;
                this.command = this.commandHistoryIndex < 0 ? '' : this.commandHistory[this.commandHistoryIndex];
            }
        }
    };
</script>

<style lang="postcss">
    @import "~xterm/src/xterm.css";
</style>
