// Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
var CONSOLE_PUSH_COUNT = 50;
var CONSOLE_PUSH_FREQ = 200;

(function initConsole() {
    window.TerminalQueue = [];
    window.Terminal = $('#terminal').terminal(function (command, term) {
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

    Socket.on('initial status', function (data) {
        Terminal.clear();
        if (data.status === 1 || data.status === 2) {
            Socket.emit('send server log');
        }
    });
})();

(function pushOutputQueue() {
    if (TerminalQueue.length > CONSOLE_PUSH_COUNT) {
        // console throttled warning show
    }

    if (TerminalQueue.length > 0) {
        for (var i = 0; i < CONSOLE_PUSH_COUNT && TerminalQueue.length > 0; i++) {
            Terminal.echo(TerminalQueue[0]);
            TerminalQueue.shift();
        }
    }

    window.setTimeout(pushOutputQueue, CONSOLE_PUSH_FREQ);
})();

$(document).ready(function () {
    $('[data-attr="power"]').click(function (event) {
        Socket.emit('set status', $(this).data('action'));
    });
    var ctc = $('#chart_cpu');
    var timeLabels = [];
    var cpuData = [];
    var CPUChart = new Chart(ctc, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [
                {
                    label: "Percent Use",
                    fill: false,
                    lineTension: 0.03,
                    backgroundColor: "#00A1CB",
                    borderColor: "#00A1CB",
                    borderCapStyle: 'butt',
                    borderDash: [],
                    borderDashOffset: 0.0,
                    borderJoinStyle: 'miter',
                    pointBorderColor: "rgba(75,192,192,1)",
                    pointBackgroundColor: "#fff",
                    pointBorderWidth: 1,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "rgba(75,192,192,1)",
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
    var memoryData = [];
    var MemoryChart = new Chart(ctm, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [
                {
                    label: "Memory Use",
                    fill: false,
                    lineTension: 0.03,
                    backgroundColor: "#01A4A4",
                    borderColor: "#01A4A4",
                    borderCapStyle: 'butt',
                    borderDash: [],
                    borderDashOffset: 0.0,
                    borderJoinStyle: 'miter',
                    pointBorderColor: "rgba(75,192,192,1)",
                    pointBackgroundColor: "#fff",
                    pointBorderWidth: 1,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "rgba(75,192,192,1)",
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

        CPUChart.update();
        MemoryChart.update();
    });

    // Update Listings on Initial Status
    Socket.on('initial status', function (data) {
        updateServerPowerControls(data.status);
    });

    // Update Listings on Status
    Socket.on('status', function (data) {
        updateServerPowerControls(data.status);
    });

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
});
