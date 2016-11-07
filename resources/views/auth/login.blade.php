{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com> --}}

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

@section('title', 'Login')


@section('right-nav')
@endsection

@section('sidebar')
@endsection

@section('resp-alerts')
@endsection

@section('resp-errors')
@endsection

@section('content')
<div class="col-md-8">
    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <strong>{{ trans('strings.whoops') }}!</strong> {{ trans('auth.errorencountered') }}<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @foreach (Alert::getMessages() as $type => $messages)
        @foreach ($messages as $message)
            <div class="alert alert-{{ $type }} alert-dismissable" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                {!! $message !!}
            </div>
        @endforeach
    @endforeach
    <form action="/auth/login" method="POST" id="login-form">
        <legend>{{ trans('strings.login') }}</legend>
        <fieldset>
            <div class="form-group">
                <label for="email" class="control-label">{{ trans('strings.email') }}</label>
                <div>
                    <input type="text" class="form-control" name="email" id="email" value="{{ old('email') }}" placeholder="{{ trans('strings.email') }}" />
                </div>
            </div>
            <div class="form-group">
                <label for="login-password" class="control-label">{{ trans('strings.password') }}</label>
                <div>
                    <input type="password" class="form-control" name="password" id="password" placeholder="{{ trans('strings.password') }}" />
                </div>
            </div>
            <div class="form-group">
                <div>
                    <label><input type="checkbox" name="remember" /> {{ trans('auth.remeberme') }}</label>
                </div>
            </div>
            <div class="form-group">
                <div>
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-primary btn-sm" value="{{ trans('strings.login') }}" />
                    <button class="btn btn-default btn-sm" onclick="window.location='/auth/password';return false;">{{ trans('auth.resetpassword') }}</button>
                </div>
            </div>
        </fieldset>
    </form>
</div>
<div class="modal fade" id="openTOTP" tabindex="-1" role="dialog">
    <div class="modal-dialog" style="width:400px;">
        <form action="/auth/login" method="POST" id="totp-form">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Two-Factor Authentication Checkpoint</h4>
                </div>
                <div class="modal-body" id="modal_insert_content">
                    <div class="form-group">
                        <label for="totp_token" class="control-label">Two-Factor Authentication Token</label>
                        <div>
                            <input class="form-control" type="text" placeholder="000111" name="totp_token" id="totp_token" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" class="btn btn-default btn-sm" value="Continue" />
                </div>
            </div>
        </form>
    </div>
</div>
<div class="col-md-3"></div>
<script type="text/javascript">
$(document).ready(function() {
    $('#login-form').one('submit', function (event) {
        event.preventDefault();
        var check_email = $('#email').val();
        $.ajax({
            type: 'POST',
            url: '/auth/login/totp',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                email: check_email
            }
        }).done(function (data) {
            if (typeof data.id !== 'undefined') {
                $('#openTOTP').modal({
                    backdrop: 'static',
                    keyboard: false
                });
                $('#openTOTP').on('shown.bs.modal', function() {
                    $('#totp_token').focus();
                });
            } else {
                $('#login-form').submit();
            }
        }).fail(function (jqXHR) {
            alert('Unable to validate potential TOTP need.');
            console.error(jqXHR);
        });
    });
    $('#totp-form').submit(function () {
        return $('#login-form :input').not(':submit').clone().hide().appendTo('#totp-form');
    });
});
</script>
@endsection
