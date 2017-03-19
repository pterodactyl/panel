// Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.

var Console = (function () {
    var CONSOLE_PUSH_COUNT = Pterodactyl.config.console_count;
    var CONSOLE_PUSH_FREQ = Pterodactyl.config.console_freq;

    var terminalQueue;
    var terminal;
    var recievedInitialLog = false;

    var cpuChart;
    var cpuData;
    var memoryChart;
    var memoryData;
    var timeLabels;

    var $terminalNotify;

    function initConsole() {
        terminalQueue = [];
        terminal = $('#terminal').terminal(function (command, term) {
            Socket.emit('send command', command);
        }, {
            greetings: '',
            name: Pterodactyl.server.uuid,
            height: 450,
            exit: false,
            prompt: Pterodactyl.server.username + ':~$ ',
            scrollOnEcho: false,
            scrollBottomOffset: 5,
            onBlur: function (terminal) {
                return false;
            }
        });

        $terminalNotify = $('#terminalNotify');
        $terminalNotify.on('click', function () {
            terminal.scroll_to_bottom();
            $terminalNotify.addClass('hidden');
        })

        terminal.on('scroll', function () {
            if (terminal.is_bottom()) {
                $terminalNotify.addClass('hidden');
            }
        })
    }

    function initGraphs() {
        var ctc = $('#chart_cpu');
        timeLabels = [];
        cpuData = [];
        cpuChart = new Chart(ctc, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [
                    {
                        label: "Percent Use",
                        fill: false,
                        lineTension: 0.03,
                        backgroundColor: "#3c8dbc",
                        borderColor: "#3c8dbc",
                        borderCapStyle: 'butt',
                        borderDash: [],
                        borderDashOffset: 0.0,
                        borderJoinStyle: 'miter',
                        pointBorderColor: "#3c8dbc",
                        pointBackgroundColor: "#fff",
                        pointBorderWidth: 1,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: "#3c8dbc",
                        pointHoverBorderColor: "rgba(220,220,220,1)",
                        pointHoverBorderWidth: 2,
                        pointRadius: 1,
                        pointHitRadius: 10,
                        data: cpuData,
                        spanGaps: false,
                    }
                ]
            },
            options: {
                title: {
                    display: true,
                    text: 'CPU Usage (as Percent Total)'
                },
                legend: {
                    display: false,
                },
                animation: {
                    duration: 1,
                }
            }
        });

        var ctm = $('#chart_memory');
        memoryData = [];
        memoryChart = new Chart(ctm, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [
                    {
                        label: "Memory Use",
                        fill: false,
                        lineTension: 0.03,
                        backgroundColor: "#3c8dbc",
                        borderColor: "#3c8dbc",
                        borderCapStyle: 'butt',
                        borderDash: [],
                        borderDashOffset: 0.0,
                        borderJoinStyle: 'miter',
                        pointBorderColor: "#3c8dbc",
                        pointBackgroundColor: "#fff",
                        pointBorderWidth: 1,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: "#3c8dbc",
                        pointHoverBorderColor: "rgba(220,220,220,1)",
                        pointHoverBorderWidth: 2,
                        pointRadius: 1,
                        pointHitRadius: 10,
                        data: memoryData,
                        spanGaps: false,
                    }
                ]
            },
            options: {
                title: {
                    display: true,
                    text: 'Memory Usage (in Megabytes)'
                },
                legend: {
                    display: false,
                },
                animation: {
                    duration: 1,
                }
            }
        });
    }

    function addSocketListeners() {
        // Update Listings on Initial Status
        Socket.on('initial status', function (data) {
            if (! recievedInitialLog) {
                updateServerPowerControls(data.status);

                if (data.status === 1 || data.status === 2) {
                    Socket.emit('send server log');
                }
            }
        });

        // Update Listings on Status
        Socket.on('status', function (data) {
            updateServerPowerControls(data.status);
        });

        Socket.on('server log', function (data) {
            if (! recievedInitialLog) {
                terminal.clear();
                terminalQueue.push(data);
                recievedInitialLog = true;
            }
        });

        Socket.on('console', function (data) {
            terminalQueue.push(data.line);
        });

        Socket.on('proc', function (proc) {
            if (cpuData.length > 10) {
                cpuData.shift();
                memoryData.shift();
                timeLabels.shift();
            }

            var cpuUse = (Pterodactyl.server.cpu > 0) ? parseFloat(((proc.data.cpu.total / Pterodactyl.server.cpu) * 100).toFixed(3).toString()) : proc.data.cpu.total;
            cpuData.push(cpuUse);
            memoryData.push(parseInt(proc.data.memory.total / (1024 * 1024)));

            var m = new Date();
            timeLabels.push($.format.date(new Date(), 'HH:mm:ss'));

            cpuChart.update();
            memoryChart.update();
        });
    }

    function pushOutputQueue() {
        if (terminalQueue.length > CONSOLE_PUSH_COUNT) {
            // console throttled warning show
        }

        if (terminalQueue.length > 0) {
            for (var i = 0; i < CONSOLE_PUSH_COUNT && terminalQueue.length > 0; i++) {
                terminal.echo(terminalQueue[0], {flush: false});
                terminalQueue.shift();
            }
            terminal.flush()

            // Show
            if (!terminal.is_bottom()) {
                $terminalNotify.removeClass('hidden');
            }
        }

        window.setTimeout(pushOutputQueue, CONSOLE_PUSH_FREQ);
    }

    function updateServerPowerControls (data) {
        // Server is On or Starting
        if(data == 1 || data == 2) {
            $('[data-attr="power"][data-action="start"]').addClass('disabled');
            $('[data-attr="power"][data-action="stop"], [data-attr="power"][data-action="restart"]').removeClass('disabled');
        } else {
            if (data == 0) {
                $('[data-attr="power"][data-action="start"]').removeClass('disabled');
            }
            $('[data-attr="power"][data-action="stop"], [data-attr="power"][data-action="restart"]').addClass('disabled');
        }

        if(data !== 0) {
            $('[data-attr="power"][data-action="kill"]').removeClass('disabled');
        } else {
            $('[data-attr="power"][data-action="kill"]').addClass('disabled');
        }
    }

    return {
        init: function () {

            initConsole();
            pushOutputQueue();
            initGraphs();
            addSocketListeners();

            $('[data-attr="power"]').click(function (event) {
                if (! $(this).hasClass('disabled')) {
                    Socket.emit('set status', $(this).data('action'));
                }
            });
        },

        getTerminal: function () {
            return terminal
        },

        getTerminalQueue: function () {
            return terminalQueue
        },
    }

})();

$(document).ready(function () {
    Console.init();
});
