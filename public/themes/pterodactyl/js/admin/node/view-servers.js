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
(function initSocket() {
    if (typeof $.notifyDefaults !== 'function') {
        console.error('Notify does not appear to be loaded.');
        return;
    }

    if (typeof io !== 'function') {
        console.error('Socket.io is reqired to use this panel.');
        return;
    }

    $.notifyDefaults({
        placement: {
            from: 'bottom',
            align: 'right'
        },
        newest_on_top: true,
        delay: 2000,
        animate: {
            enter: 'animated zoomInDown',
            exit: 'animated zoomOutDown'
        }
    });

    var notifySocketError = false;
    // Main Socket Object
    window.Socket = io(Pterodactyl.node.scheme + '://' + Pterodactyl.node.fqdn + ':' + Pterodactyl.node.daemonListen + '/v1/stats/', {
        'query': 'token=' + Pterodactyl.node.daemonSecret,
    });

    // Socket Failed to Connect
    Socket.io.on('connect_error', function (err) {
        if(typeof notifySocketError !== 'object') {
            notifySocketError = $.notify({
                message: 'There was an error attempting to establish a WebSocket connection to the Daemon. This panel will not work as expected.<br /><br />' + err,
            }, {
                type: 'danger',
                delay: 0
            });
        }
    });

    // Connected to Socket Successfully
    Socket.on('connect', function () {
        if (notifySocketError !== false) {
            notifySocketError.close();
            notifySocketError = false;
        }
    });

    Socket.on('error', function (err) {
        console.error('There was an error while attemping to connect to the websocket: ' + err + '\n\nPlease try loading this page again.');
    });

    Socket.on('live-stats', function (data) {
        $.each(data.servers, function (uuid, info) {
            var element = $('tr[data-server="' + uuid + '"]');
            switch (info.status) {
                case 0:
                    element.find('[data-action="status"]').html('<span class="label label-danger">Offline</span>');
                    break;
                case 1:
                    element.find('[data-action="status"]').html('<span class="label label-success">Online</span>');
                    break;
                case 2:
                    element.find('[data-action="status"]').html('<span class="label label-info">Starting</span>');
                    break;
                case 3:
                    element.find('[data-action="status"]').html('<span class="label label-info">Stopping</span>');
                    break;
                case 20:
                    element.find('[data-action="status"]').html('<span class="label label-warning">Installing</span>');
                    break;
                case 30:
                    element.find('[data-action="status"]').html('<span class="label label-warning">Suspended</span>');
                    break;
            }
            if (info.status !== 0) {
                var cpuMax = element.find('[data-action="cpu"]').data('cpumax');
                var currentCpu = info.proc.cpu.total;
                if (cpuMax !== 0) {
                    currentCpu = parseFloat(((info.proc.cpu.total / cpuMax) * 100).toFixed(2).toString());
                }
                element.find('[data-action="memory"]').html(parseInt(info.proc.memory.total / (1024 * 1024)));
                element.find('[data-action="cpu"]').html(currentCpu);
            } else {
                element.find('[data-action="memory"]').html('--');
                element.find('[data-action="cpu"]').html('--');
            }
        });
    });
})();
