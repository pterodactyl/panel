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
var CONSOLE_PUSH_COUNT = Pterodactyl.config.console_count || 50;
var CONSOLE_PUSH_FREQ = Pterodactyl.config.console_freq || 200;
var CONSOLE_OUTPUT_LIMIT = Pterodactyl.config.console_limit || 2000;

var KEYCODE_UP_ARROW = 38;
var KEYCODE_DOWN_ARROW = 40;

var AnsiUp = new AnsiUp;
AnsiUp.use_classes = true;

var $terminal = $('#terminal');
var $terminalInput = $('.terminal_input--input');
var $scrollNotify = $('#terminalNotify');

$(document).ready(function () {
    var storage = window.localStorage;
    var activeHx = [];
    var currentHxIndex = 0;
    var currentKeyCount = 0;

    var storedConsoleHistory = storage.getItem('console_hx_' + Pterodactyl.server.uuid);
    try {
        activeHx = JSON.parse(storedConsoleHistory) || [];
        currentKeyCount = activeHx.length - 1;
    } catch (ex) {
        //
    }

    $terminalInput.focus();
    $('.terminal_input--prompt, #terminal_input, #terminal, #terminalNotify').on('click', function () {
        $terminalInput.focus();
    });

    $terminalInput.on('keyup', function (e) {
        if (e.which === KEYCODE_DOWN_ARROW || e.which === KEYCODE_UP_ARROW) {
            var value = consoleHistory(e.which);

            if (value !== false) {
                $terminalInput.val(value);
            }
        }

        if (e.which === 27) {
            $(this).val('');
        }

        if (e.which === 13) {
            saveToHistory($(this).val());
            Socket.emit((ConsoleServerStatus !== 0) ? 'send command' : 'set status', $(this).val());

            $(this).val('');
        }
    });

    function consoleHistory(key) {
        // Get previous
        if (key === KEYCODE_UP_ARROW) {
            // currentHxIndex++;
            var index = activeHx.length - (currentHxIndex + 1);

            if (typeof activeHx[index - 1] === 'undefined') {
                return activeHx[index];
            }

            currentHxIndex++;
            return activeHx[index];
        }

        // Get more recent
        if (key === KEYCODE_DOWN_ARROW) {
            var index = activeHx.length - currentHxIndex;

            if (typeof activeHx[index + 1] === 'undefined') {
                return activeHx[index];
            }

            currentHxIndex--;
            return activeHx[index];
        }
    }

    function saveToHistory(command) {
        if (command.length === 0) {
            return;
        }

        if (activeHx.length >= 50) {
            activeHx.pop();
        }

        currentHxIndex = 0;
        currentKeyCount++;
        activeHx[currentKeyCount] = command;

        storage.setItem('console_hx_' + Pterodactyl.server.uuid, JSON.stringify(activeHx));
    }
});

$terminal.on('scroll', function () {
    if ($(this).scrollTop() + $(this).innerHeight() < $(this)[0].scrollHeight) {
        $scrollNotify.removeClass('hidden');
    } else {
        $scrollNotify.addClass('hidden');
    }
});

window.scrollToBottom = function () {
    $terminal.scrollTop($terminal[0].scrollHeight);
};

function pushToTerminal(string) {
    $terminal.append('<div class="cmd">' + AnsiUp.ansi_to_html(string + '\u001b[0m') + '</div>');
}

(function initConsole() {
    window.TerminalQueue = [];
    window.ConsoleServerStatus = 0;
    window.ConsoleElements = 0;

    $scrollNotify.on('click', function () {
        window.scrollToBottom();
        $scrollNotify.addClass('hidden');
    });
})();

(function pushOutputQueue() {
    if (TerminalQueue.length > CONSOLE_PUSH_COUNT) {
        // console throttled warning show
    }

    if (TerminalQueue.length > 0) {
        for (var i = 0; i < CONSOLE_PUSH_COUNT && TerminalQueue.length > 0; i++) {
            pushToTerminal(TerminalQueue[0]);

            if (! $scrollNotify.is(':visible')) {
                window.scrollToBottom();
            }

            window.ConsoleElements++;
            TerminalQueue.shift();
        }

        var removeElements = window.ConsoleElements - CONSOLE_OUTPUT_LIMIT;
        if (removeElements > 0) {
            $('#terminal').find('.cmd').slice(0, removeElements).remove();
            window.ConsoleElements = window.ConsoleElements - removeElements;
        }
    }

    window.setTimeout(pushOutputQueue, CONSOLE_PUSH_FREQ);
})();

(function setupSocketListeners() {
    // Update Listings on Initial Status
    Socket.on('initial status', function (data) {
        ConsoleServerStatus = data.status;
        updateServerPowerControls(data.status);

        if (data.status === 1 || data.status === 2) {
            Socket.emit('send server log');
        }
    });

    // Update Listings on Status
    Socket.on('status', function (data) {
        ConsoleServerStatus = data.status;
        updateServerPowerControls(data.status);
    });

    // Skips the queue so we don't wait
    // 10 minutes to load the log...
    Socket.on('server log', function (data) {
        $('#terminal').html('');
        data.split(/\n/g).forEach(function (item) {
            pushToTerminal(item);
            window.scrollToBottom();
        });
    });

    Socket.on('console', function (data) {
        data.line.split(/\n/g).forEach(function (item) {
            TerminalQueue.push(item);
        });
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
