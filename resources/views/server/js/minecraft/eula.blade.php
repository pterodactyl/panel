$(window).load(function () {
    socket.on('console', function (data) {
        if (data.line.indexOf('You need to agree to the EULA in order to run the server') > -1) {
            swal({
                title: 'EULA Acceptance',
                text: 'By pressing \'I Accept\' below you are indicating your agreement to the <a href="https://account.mojang.com/documents/minecraft_eula" target="_blank">Mojang EULA</a>.',
                type: 'info',
                html: true,
                showCancelButton: true,
                showConfirmButton: true,
                cancelButtonText: 'I do not Accept',
                confirmButtonText: 'I Accept',
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, function () {
                $.ajax({
                    type: 'POST',
                    url: '{{ route('server.files.save', $server->uuidShort) }}',
                    headers: { 'X-CSRF-Token': '{{ csrf_token() }}' },
                    data: {
                        file: 'eula.txt',
                        contents: 'eula=true'
                    }
                }).done(function (data) {
                    $('[data-attr="power"][data-action="start"]').trigger('click');
                    swal({
                        type: 'success',
                        title: '',
                        text: 'The EULA for this server has been accepted, restarting server now.',
                    });
                }).fail(function (jqXHR) {
                    console.error(jqXHR);
                    swal({
                        title: 'Whoops!',
                        text: 'An error occured while attempting to set the EULA as accepted: ' . jqXHR.responseJSON.error,
                        type: 'error'
                    })
                });
            });
        }
    });
});
