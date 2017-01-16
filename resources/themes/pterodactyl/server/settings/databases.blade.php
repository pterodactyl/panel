{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}

{{-- Permission is hereby granted, free of charge, to any person obtaining a copy --}}
{{-- of this software and associated documentation files (the "Software"), to deal --}}
{{-- in the Software without restriction, including without limitation the rights --}}
{{-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell --}}
{{-- copies of the Software, and to permit persons to whom the Software is --}}
{{-- furnished to do so, subject to the following conditions: --}}

{{-- The above copyright notice and this permission notice shall be included in all --}}
{{-- copies or substantial portions of the Software. --}}

{{-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR --}}
{{-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, --}}
{{-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE --}}
{{-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER --}}
{{-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, --}}
{{-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE --}}
{{-- SOFTWARE. --}}
@extends('layouts.master')

@section('title')
    Databases
@endsection

@section('content-header')
    <h1>Databases<small>All databases available for this server.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">{{ trans('strings.home') }}</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li>{{ trans('strings.configuration') }}</li>
        <li class="active">{{ trans('strings.databases') }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Your Databases</h3>
            </div>
            @if(count($databases) > 0)
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>Database</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>MySQL Host</th>
                            </tr>
                            @foreach($databases as $database)
                                <tr>
                                    <td>{{ $database->database }}</td>
                                    <td>{{ $database->username }}</td>
                                    <td><code>{{ Crypt::decrypt($database->password) }}</code>
                                        @can('reset-db-password', $server)
                                            <button class="btn btn-xs btn-primary pull-right" data-action="reset-database-password" data-id="{{ $database->id }}"><i class="fa fa-fw fa-refresh"></i> Reset Password</button>
                                        @endcan
                                    </td>
                                    <td><code>{{ $database->a_host }}:{{ $database->a_port }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="box-body">
                    <div class="callout callout-info callout-nomargin">
                        There are no databases listed for this server.
                        @if(Auth::user()->root_admin === 1)
                            <a href="{{ route('admin.servers.view', [
                                'id' => $server->id,
                                'tab' => 'tab_database'
                            ]) }}" target="_blank">Add a new database.</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
    <script>
    @can('reset-db-password', $server)
        $('[data-action="reset-database-password"]').click(function (e) {
            e.preventDefault();
            var block = $(this);
            $(this).find('i').addClass('fa-spin');
            $.ajax({
                type: 'POST',
                url: Router.route('server.ajax.reset-database-password', { server: Pterodactyl.server.uuidShort }),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
                },
                data: {
                    'database': $(this).data('id')
                }
            }).done(function (data) {
                block.parent().find('code').html(data);
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error(jqXHR);
                var error = 'An error occured while trying to process this request.';
                if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                    error = jqXHR.responseJSON.error;
                }
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: error
                });
            }).always(function () {
                block.find('i').removeClass('fa-spin');
            });
        });
    @endcan
    </script>
@endsection
