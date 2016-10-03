"use strict";

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
class ActionsClass {
    constructor(element, menu) {
        this.element = element;
        this.menu = menu;
    }

    destroy() {
        this.element = undefined;
    }

    move() {
        alert($(this.element).data('path'));
    }

    download() {
        var baseURL = $(this.menu).find('li[data-action="download"] a').attr('href');
        var toURL = baseURL + $(this.element).find('td[data-identifier="name"]').data('name');

        window.location = toURL;
    }

    rename() {
        const nameBlock = $(this.element).find('td[data-identifier="name"]');
        const currentLink = nameBlock.find('a');
        const currentName = decodeURIComponent(nameBlock.attr('data-name'));
        const attachEditor = `
            <input class="form-control input-sm" type="text" value="${currentName}" />
            <span class="input-loader"><i class="fa fa-refresh fa-spin fa-fw"></i></span>
        `;

        nameBlock.html(attachEditor);
        const inputField = nameBlock.find('input');
        const inputLoader = nameBlock.find('.input-loader');

        inputField.focus();
        inputField.on('blur keypress', e => {
            // Save Field
            if (e.type === 'blur' || (e.type === 'keypress' && e.which !== 13)) {
                // Escape Key Pressed, don't save.
                if (e.which === 27 || e.type === 'blur') {
                    if (!_.isEmpty(currentLink)) {
                        nameBlock.html(currentLink);
                    } else {
                        nameBlock.html(currentName);
                    }
                    inputField.remove();
                    ContextMenu.run();
                }
                return;
            }

            inputLoader.show();
            const currentPath = decodeURIComponent(nameBlock.data('path'));

            $.ajax({
                type: 'POST',
                headers: {
                    'X-Access-Token': '{{ $server->daemonSecret }}',
                    'X-Access-Server': '{{ $server->uuid }}'
                },
                contentType: 'application/json; charset=utf-8',
                url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/files/rename',
                timeout: 10000,
                data: JSON.stringify({
                    from: `${currentPath}${currentName}`,
                    to: `${currentPath}${inputField.val()}`,
                }),
            }).done(data => {
                nameBlock.attr('data-name', inputField.val());
                if (!_.isEmpty(currentLink)) {
                    const newLink = currentLink.attr('href').substr(0, currentLink.attr('href').lastIndexOf('/')) + '/' + inputField.val();
                    currentLink.attr('href', newLink);
                    nameBlock.html(
                        currentLink.html(inputField.val())
                    );
                } else {
                    nameBlock.html(inputField.val());
                }
                inputField.remove();
            }).fail(jqXHR => {
                nameBlock.addClass('has-error');
                inputLoader.remove();
                console.error(jqXHR);
                var error = 'An error occured while trying to process this request.';
                if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                    error = jqXHR.responseJSON.error;
                }
                swal({
                    type: 'error',
                    title: '',
                    text: error,
                });
            }).always(() => {
                inputLoader.remove();
            });
        });
    }
}
