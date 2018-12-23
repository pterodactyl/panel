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
(function updateServerStatus() {
    var Status = {
        0: 'Offline',
        1: 'Online',
        2: 'Starting',
        3: 'Stopping'
    };
    $('.dynamic-update').each(function (index, data) {
        var element = $(this);
        var serverShortUUID = $(this).data('server');

        $.ajax({
            type: 'GET',
            url: Router.route('index.status', { server: serverShortUUID }),
            timeout: 5000,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
            }
        }).done(function (data) {
            if (typeof data.status === 'undefined') {
                element.find('[data-action="status"]').html('<span class="label label-default">Error</span>');
                return;
            }
            switch (data.status) {
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
            if (data.status > 0 && data.status < 4) {
                var cpuMax = element.find('[data-action="cpu"]').data('cpumax');
                var currentCpu = data.proc.cpu.total;
                if (cpuMax !== 0) {
                    currentCpu = parseFloat(((data.proc.cpu.total / cpuMax) * 100).toFixed(2).toString());
                }
                if (data.status !== 0) {
                    var cpuMax = element.find('[data-action="cpu"]').data('cpumax');
                    var currentCpu = data.proc.cpu.total;
                    if (cpuMax !== 0) {
                        currentCpu = parseFloat(((data.proc.cpu.total / cpuMax) * 100).toFixed(2).toString());
                    }
                    element.find('[data-action="memory"]').html(parseInt(data.proc.memory.total / (1024 * 1024)));
                    element.find('[data-action="cpu"]').html(currentCpu);
                } else {
                    element.find('[data-action="memory"]').html('--');
                    element.find('[data-action="cpu"]').html('--');
                }
            }
        }).fail(function (jqXHR) {
            if (jqXHR.status === 504) {
                element.find('[data-action="status"]').html('<span class="label label-default">Gateway Timeout</span>');
            } else {
                element.find('[data-action="status"]').html('<span class="label label-default">Error</span>');
            }
        });
    }).promise().done(function () {
        setTimeout(updateServerStatus, 10000);
    });
})();
