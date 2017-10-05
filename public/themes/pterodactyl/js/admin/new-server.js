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
$(document).ready(function() {
    $('#pServiceId').select2({
        placeholder: 'Select a Service',
    }).change();
    $('#pOptionId').select2({
        placeholder: 'Select a Service Option',
    });
    $('#pPackId').select2({
        placeholder: 'Select a Service Pack',
    });
    $('#pLocationId').select2({
        placeholder: 'Select a Location',
    }).change();
    $('#pNodeId').select2({
        placeholder: 'Select a Node',
    });
    $('#pAllocation').select2({
        placeholder: 'Select a Default Allocation',
    });
    $('#pAllocationAdditional').select2({
        placeholder: 'Select Additional Allocations',
    });

    $('#pUserId').select2({
        ajax: {
            url: Router.route('admin.users.json'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function (data, params) {
                return { results: data };
            },
            cache: true,
        },
        escapeMarkup: function (markup) { return markup; },
        minimumInputLength: 2,
        templateResult: function (data) {
            if (data.loading) return data.text;

            return '<div class="user-block"> \
                <img class="img-circle img-bordered-xs" src="https://www.gravatar.com/avatar/' + data.md5 + '?s=120" alt="User Image"> \
                <span class="username"> \
                    <a href="#">' + data.name_first + ' ' + data.name_last +'</a> \
                </span> \
                <span class="description"><strong>' + data.email + '</strong> - ' + data.username + '</span> \
            </div>';
        },
        templateSelection: function (data) {
            return '<div> \
                <span> \
                    <img class="img-rounded img-bordered-xs" src="https://www.gravatar.com/avatar/' + data.md5 + '?s=120" style="height:28px;margin-top:-4px;" alt="User Image"> \
                </span> \
                <span style="padding-left:5px;"> \
                    ' + data.name_first + ' ' + data.name_last + ' (<strong>' + data.email + '</strong>) \
                </span> \
            </div>';
        }
    });
});

function hideLoader() {
    $('#allocationLoader').hide();
}

function showLoader() {
    $('#allocationLoader').show();
}

var lastActiveBox = null;
$(document).on('click', function (event) {
    if (lastActiveBox !== null) {
        lastActiveBox.removeClass('box-primary');
    }

    lastActiveBox = $(event.target).closest('.box');
    lastActiveBox.addClass('box-primary');
});

var currentLocation = null;
var curentNode = null;
var NodeData = [];

$('#pLocationId').on('change', function (event) {
    showLoader();
    currentLocation = $(this).val();
    currentNode = null;

    $.ajax({
        method: 'POST',
        url: Router.route('admin.servers.new.nodes'),
        headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
        data: { location: currentLocation },
    }).done(function (data) {
        NodeData = data;
        $('#pNodeId').html('').select2({data: data}).change();
    }).fail(function (jqXHR) {
        cosole.error(jqXHR);
        currentLocation = null;
    }).always(hideLoader);
});

$('#pNodeId').on('change', function (event) {
    currentNode = $(this).val();
    $.each(NodeData, function (i, v) {
        if (v.id == currentNode) {
            $('#pAllocation').html('').select2({
                data: v.allocations,
                placeholder: 'Select a Default Allocation',
            });
            $('#pAllocationAdditional').html('').select2({
                data: v.allocations,
                placeholder: 'Select Additional Allocations',
            })
        }
    });
});

$('#pServiceId').on('change', function (event) {
    $('#pOptionId').html('').select2({
        data: $.map(_.get(Pterodactyl.services, $(this).val() + '.options', []), function (item) {
            return {
                id: item.id,
                text: item.name,
            };
        }),
    }).change();
});

$('#pOptionId').on('change', function (event) {
    var parentChain = _.get(Pterodactyl.services, $('#pServiceId').val(), null);
    var objectChain = _.get(parentChain, 'options.' + $(this).val(), null);

    $('#pDefaultContainer').val(_.get(objectChain, 'docker_image', 'not defined!'));

    if (!_.get(objectChain, 'startup', false)) {
        $('#pStartup').val(_.get(parentChain, 'startup', 'ERROR: Startup Not Defined!'));
    } else {
        $('#pStartup').val(_.get(objectChain, 'startup'));
    }

    $('#pPackId').html('').select2({
        data: [{ id: 0, text: 'No Service Pack' }].concat(
            $.map(_.get(objectChain, 'packs', []), function (item, i) {
                return {
                    id: item.id,
                    text: item.name + ' (' + item.version + ')',
                };
            })
        ),
    });

    $('#appendVariablesTo').html('');
    $.each(_.get(objectChain, 'variables', []), function (i, item) {
        var isRequired = (item.required === 1) ? '<span class="label label-danger">Required</span> ' : '';
        var dataAppend = ' \
            <div class="form-group col-sm-6"> \
                <label for="var_ref_' + item.id + '" class="control-label">' + isRequired + item.name + '</label> \
                <input type="text" id="var_ref_' + item.id + '" autocomplete="off" name="env_' + item.env_variable + '" class="form-control" value="' + item.default_value + '" /> \
                <p class="text-muted small">' + item.description + '<br /> \
                <strong>Access in Startup:</strong> <code>{{' + item.env_variable + '}}</code><br /> \
                <strong>Validation Rules:</strong> <code>' + item.rules + '</code></small></p> \
            </div> \
        ';
        $('#appendVariablesTo').append(dataAppend);
    });
});
