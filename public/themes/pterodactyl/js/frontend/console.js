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
var CONSOLE_PUSH_COUNT = Pterodactyl.config.console_count || 10;
var CONSOLE_PUSH_FREQ = Pterodactyl.config.console_freq || 200;
var CONSOLE_OUTPUT_LIMIT = Pterodactyl.config.console_limit || 2000;
var InitialLogSent = false;

(function initConsole() {
    window.TerminalQueue = [];
    window.ConsoleServerStatus = 0;
    window.Terminal = $('#terminal').terminal(function (command, term) {
        Socket.emit((ConsoleServerStatus !== 0) ? 'send command' : 'set status', command);
    }, {
        greetings: '',
        name: Pterodactyl.server.uuid,
        height: 450,
        exit: false,
        echoCommand: false,
        outputLimit: CONSOLE_OUTPUT_LIMIT,
        prompt: Pterodactyl.server.username + ':~$ ',
        scrollOnEcho: false,
        scrollBottomOffset: 5,
        onBlur: function (terminal) {
            return false;
        }
    });

    window.TerminalNotifyElement = $('#terminalNotify');
    TerminalNotifyElement.on('click', function () {
        Terminal.scroll_to_bottom();
        TerminalNotifyElement.addClass('hidden');
    })

    Terminal.on('scroll', function () {
        if (Terminal.is_bottom()) {
            TerminalNotifyElement.addClass('hidden');
        }
    })
})();

(function pushOutputQueue() {
    if (TerminalQueue.length > CONSOLE_PUSH_COUNT) {
        // console throttled warning show
    }

    if (TerminalQueue.length > 0) {
        for (var i = 0; i < CONSOLE_PUSH_COUNT && TerminalQueue.length > 0; i++) {
            Terminal.echo(TerminalQueue[0], { flush: false });
            TerminalQueue.shift();
        }

        // Flush after looping through all.
        Terminal.flush();

        // Show Warning
        if (! Terminal.is_bottom()) {
            TerminalNotifyElement.removeClass('hidden');
        }
    }

    window.setTimeout(pushOutputQueue, CONSOLE_PUSH_FREQ);
})();

(function setupSocketListeners() {
    // Update Listings on Initial Status
    Socket.on('initial status', function (data) {
        ConsoleServerStatus = data.status;
        if (! InitialLogSent) {
            updateServerPowerControls(data.status);

            if (data.status === 1 || data.status === 2) {
                Socket.emit('send server log');
            }
        }
    });

    // Update Listings on Status
    Socket.on('status', function (data) {
        ConsoleServerStatus = data.status;
        updateServerPowerControls(data.status);
    });

    Socket.on('server log', function (data) {
        if (! InitialLogSent) {
            Terminal.clear();
            TerminalQueue.push(data);
            InitialLogSent = true;
        }
    });

    Socket.on('console', function (data) {
        TerminalQueue.push(data.line);
    });
})();


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

$(document).ready(function () {
    $('[data-attr="power"]').click(function (event) {
        if (! $(this).hasClass('disabled')) {
            Socket.emit('set status', $(this).data('action'));
        }
    });

    (function setupChartElements() {
        if (typeof SkipConsoleCharts !== 'undefined') {
            return;
        }

        Socket.on('proc', function (proc) {
            if (CPUData.length > 10) {
                CPUData.shift();
                MemoryData.shift();
                TimeLabels.shift();
            }

            var cpuUse = (Pterodactyl.server.cpu > 0) ? parseFloat(((proc.data.cpu.total / Pterodactyl.server.cpu) * 100).toFixed(3).toString()) : proc.data.cpu.total;
            CPUData.push(cpuUse);
            MemoryData.push(parseInt(proc.data.memory.total / (1024 * 1024)));

            TimeLabels.push($.format.date(new Date(), 'HH:mm:ss'));

            CPUChart.update();
            MemoryChart.update();
        });

        var ctc = $('#chart_cpu');
        var TimeLabels = [];
        var CPUData = [];
        var CPUChart = new Chart(ctc, {
            type: 'line',
            data: {
                labels: TimeLabels,
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
                        data: CPUData,
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
        MemoryData = [];
        MemoryChart = new Chart(ctm, {
            type: 'line',
            data: {
                labels: TimeLabels,
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
                        data: MemoryData,
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
    })();
});
