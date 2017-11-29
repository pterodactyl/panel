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
    function setupSelect2() {
        $('select[name="tasks[time_value][]"]').select2();
        $('select[name="tasks[time_interval][]"]').select2();
        $('select[name="tasks[action][]"]').select2();
    }

    setupSelect2();

    $('[data-action="update-field"]').on('change', function (event) {
        event.preventDefault();
        var updateField = $(this).data('field');
        var selected = $(this).map(function (i, opt) {
            return $(opt).val();
        }).toArray();
        if (selected.length === $(this).find('option').length) {
            $('input[name=' + updateField + ']').val('*');
        } else {
            $('input[name=' + updateField + ']').val(selected.join(','));
        }
    });

    $('button[data-action="add-new-task"]').on('click', function () {
        if ($('#containsTaskList').find('.task-list-item').length >= 5) {
            swal('Task Limit Reached', 'You may only assign a maximum of 5 tasks to one schedule.');
            return;
        }

        var clone = $('div[data-target="task-clone"]').clone();
        clone.insertBefore('#taskAppendBefore').removeAttr('data-target');
        clone.find('select:first').attr('selected');
        clone.find('input').val('');
        clone.find('span.select2-container').remove();
        clone.find('div[data-attribute="remove-task-element"]').addClass('input-group').find('div.input-group-btn').removeClass('hidden');
        clone.find('button[data-action="remove-task"]').on('click', function () {
            clone.remove();
        });
        setupSelect2();
        $(this).data('element', clone);
    });
});
