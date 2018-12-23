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
$('#console-popout').on('click', function (event) {
    event.preventDefault();
    window.open($(this).attr('href'), 'Pterodactyl Console', 'width=800,height=400');
});
var Server = (function ()  {

    function initSocket() {
        if (typeof $.notifyDefaults !== 'function') {
            console.error('Notify does not appear to be loaded.');
            return;
        }

        if (typeof io !== 'function') {
            console.error('Socket.io is required to use this panel.');
            return;
        }

        $.notifyDefaults({
            placement: {
                from: 'bottom',
                align: 'right'
            },
            newest_on_top: true,
            delay: 2000,
            offset: {
                x: 20,
                y: 60,
            },
            animate: {
                enter: 'animated bounceInUp',
                exit: 'animated bounceOutDown'
            }
        });

        var notifySocketError = false;

        window.Socket = io(Pterodactyl.node.scheme + '://' + Pterodactyl.node.fqdn + ':' + Pterodactyl.node.daemonListen + '/v1/ws/' + Pterodactyl.server.uuid, {
            'query': 'token=' + Pterodactyl.server.daemonSecret,
        });

        Socket.on('error', function (err) {
            if(typeof notifySocketError !== 'object') {
                notifySocketError = $.notify({
                    message: 'There was an error attempting to establish a WebSocket connection to the Daemon. This panel will not work as expected.<br /><br />' + err,
                }, {
                    type: 'danger',
                    delay: 0,
                });
            }
            setStatusIcon(999);
        });

        Socket.io.on('connect_error', function (err) {
            if(typeof notifySocketError !== 'object') {
                notifySocketError = $.notify({
                    message: 'There was an error attempting to establish a WebSocket connection to the Daemon. This panel will not work as expected.<br /><br />' + err,
                }, {
                    type: 'danger',
                    delay: 0,
                });
            }
            setStatusIcon(999);
        });

        // Connected to Socket Successfully
        Socket.on('connect', function () {
            if (notifySocketError !== false) {
                notifySocketError.close();
                notifySocketError = false;
            }
        });

        Socket.on('initial status', function (data) {
            setStatusIcon(data.status);
        });

        Socket.on('status', function (data) {
            setStatusIcon(data.status);
        });
    }

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
                $('#server_status_icon').html('<i class="fa fa-question-circle text-danger"></i> Connection Error');
                break;
        }
    }

    return {
        init: function () {
            initSocket();
        },

        setStatusIcon: setStatusIcon,
    }

})();

Server.init();
