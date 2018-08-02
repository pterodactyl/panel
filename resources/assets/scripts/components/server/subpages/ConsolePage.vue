<template>
    <div>
        <div class="text-xs font-mono">
            <div class="rounded-t p-2 bg-black overflow-scroll w-full" style="min-height: 16rem;max-height:64rem;">
                <div v-if="!connected">
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
    import {mapState} from 'vuex';

    Terminal.applyAddon(TerminalFit);

    export default {
        name: 'console-page',
        computed: {
            ...mapState('socket', ['connected']),
        },

        watch: {
            /**
             * Watch the connected variable and when it becomes true request the server logs.
             *
             * @param {Boolean} state
             */
            connected: function (state) {
                if (state) {
                    this.$socket.emit('send server log');
                }
            }
        },

        /**
         * Listen for specific socket.io emits from the server.
         */
        sockets: {
            'server log': function (data) {
                data.split(/\n/g).forEach(line => {
                    this.terminal.writeln(line);
                });
            },

            'console': function (data) {
                data.line.split(/\n/g).forEach(line => {
                    this.terminal.writeln(line);
                });
            }
        },

        /**
         * Mount the component and setup all of the terminal actions. Also fetches the initial
         * logs from the server to populate into the terminal if the socket is connected. If the
         * socket is not connected this will occur automatically when it connects.
         */
        mounted: function () {
            this.terminal.open(this.$refs.terminal);
            this.terminal.fit();
            this.terminal.clear();

            if (this.connected) {
                this.$socket.emit('send server log');
            }
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
            };
        },

        methods: {
            /**
             * Send a command to the server using the configured websocket.
             */
            sendCommand: function () {
                this.commandHistoryIndex = -1;
                this.commandHistory.unshift(this.command);
                this.$socket.emit('send command', this.command);
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
