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

var Tasks = (function () {

    function initTaskFunctions() {
        $('[data-action="delete-task"]').click(function (event) {
            var self = $(this);
            swal({
                type: 'error',
                title: 'Delete Task?',
                text: 'Are you sure you want to delete this task? There is no undo.',
                showCancelButton: true,
                allowOutsideClick: true,
                closeOnConfirm: false,
                confirmButtonText: 'Delete Task',
                confirmButtonColor: '#d9534f',
                showLoaderOnConfirm: true
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: Router.route('server.tasks.delete', {
                        server: Pterodactyl.server.uuidShort,
                        id: self.data('id'),
                    }),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
                    }
                }).done(function (data) {
                    swal({
                        type: 'success',
                        title: '',
                        text: 'Task has been deleted.'
                    });
                    self.parent().parent().slideUp();
                }).fail(function (jqXHR) {
                    console.error(jqXHR);
                    swal({
                        type: 'error',
                        title: 'Whoops!',
                        text: 'An error occured while attempting to delete this task.'
                    });
                });
            });
        });
        $('[data-action="toggle-task"]').click(function (event) {
            var self = $(this);
            swal({
                type: 'info',
                title: 'Toggle Task',
                text: 'This will toggle the selected task.',
                showCancelButton: true,
                allowOutsideClick: true,
                closeOnConfirm: false,
                confirmButtonText: 'Continue',
                showLoaderOnConfirm: true
            }, function () {
                $.ajax({
                    method: 'POST',
                    url: Router.route('server.tasks.toggle', {
                        server: Pterodactyl.server.uuidShort,
                        id: self.data('id'),
                    }),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
                    }
                }).done(function (data) {
                    swal({
                        type: 'success',
                        title: '',
                        text: 'Task has been toggled.'
                    });
                    if (data.status !== 1) {
                        self.parent().parent().addClass('muted muted-hover');
                    } else {
                        self.parent().parent().removeClass('muted muted-hover');
                    }
                }).fail(function (jqXHR) {
                    console.error(jqXHR);
                    swal({
                        type: 'error',
                        title: 'Whoops!',
                        text: 'An error occured while attempting to toggle this task.'
                    });
                });
            });
        });
    }

    return {
        init: function () {
            initTaskFunctions();
        }
    }

})();

Tasks.init();
