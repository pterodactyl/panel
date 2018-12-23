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

$(document).ready(function () {
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-action="delete-schedule"]').click(function () {
        var self = $(this);
        swal({
            type: 'error',
            title: 'Delete Schedule?',
            text: 'Are you sure you want to delete this schedule? There is no undo.',
            showCancelButton: true,
            allowOutsideClick: true,
            closeOnConfirm: false,
            confirmButtonText: 'Delete Schedule',
            confirmButtonColor: '#d9534f',
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'DELETE',
                url: Router.route('server.schedules.view', {
                    server: Pterodactyl.server.uuidShort,
                    schedule: self.data('schedule-id'),
                }),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
                }
            }).done(function (data) {
                swal({
                    type: 'success',
                    title: '',
                    text: 'Schedule has been deleted.'
                });
                self.parent().parent().slideUp();
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: 'An error occurred while attempting to delete this schedule.'
                });
            });
        });
    });

    $('[data-action="trigger-schedule"]').click(function (event) {
        event.preventDefault();
        var self = $(this);
        swal({
            type: 'info',
            title: 'Trigger Schedule',
            text: 'This will run the selected schedule now.',
            showCancelButton: true,
            allowOutsideClick: true,
            closeOnConfirm: false,
            confirmButtonText: 'Continue',
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'POST',
                url: Router.route('server.schedules.trigger', {
                    server: Pterodactyl.server.uuidShort,
                    schedule: self.data('schedule-id'),
                }),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
                },
            }).done(function (data) {
                swal({
                    type: 'success',
                    title: '',
                    text: 'Schedule has been added to the next-run queue.'
                });
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: 'An error occurred while attempting to trigger this schedule.'
                });
            });
        });
    });

    $('[data-action="toggle-schedule"]').click(function (event) {
        var self = $(this);
        swal({
            type: 'info',
            title: 'Toggle Schedule',
            text: 'This will toggle the selected schedule.',
            showCancelButton: true,
            allowOutsideClick: true,
            closeOnConfirm: false,
            confirmButtonText: 'Continue',
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'POST',
                url: Router.route('server.schedules.toggle', {
                    server: Pterodactyl.server.uuidShort,
                    schedule: self.data('schedule-id'),
                }),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
                }
            }).done(function (data) {
                swal({
                    type: 'success',
                    title: '',
                    text: 'Schedule has been toggled.'
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
                    text: 'An error occurred while attempting to toggle this schedule.'
                });
            });
        });
    });
});
