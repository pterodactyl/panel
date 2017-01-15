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
(function initSocket() {
    if (typeof io !== 'function') {
        console.error('Socket.io is reqired to use this panel.');
        return;
    }

    window.Socket = io(Pterodactyl.node.scheme + '://' + Pterodactyl.node.fqdn + ':' + Pterodactyl.node.daemonListen + '/ws/' + Pterodactyl.server.uuid, {
        'query': 'token=' + Pterodactyl.server.daemonSecret,
    });

    Socket.io.on('connect_error', function (err) {
        console.error('Could not connect to socket.io.', err);
    });

    // Connected to Socket Successfully
    Socket.on('connect', function () {
        console.log('connected to socket');
    });

    Socket.on('initial status', function (data) {
        setStatusIcon(data.status);
    });

    Socket.on('status', function (data) {
        setStatusIcon(data.status);
    });

    Socket.on('console', function (data) {
        TerminalQueue.push(data.line);
    });
})();

function setStatusIcon(status) {
    switch (status) {
        case 0:
            $('#server_status_icon').html('<i class="fa fa-circle text-danger"></i> Offline');
            break;
        case 1:
            $('#server_status_icon').html('<i class="fa fa-circle text-success"></i> Online');
            break;
        case 2:
            $('#server_status_icon').html('<i class="fa fa-circle text-warning"></i> Starting');
            break;
        case 3:
            $('#server_status_icon').html('<i class="fa fa-circle text-warning"></i> Stopping');
            break;
        default:
            break;
    }
}
