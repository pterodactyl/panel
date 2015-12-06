@extends('layouts.master')

@section('title', 'Account TOTP Settings')

@section('sidebar-server')
@endsection

@section('content')
<div class="col-md-9">
    @foreach (Alert::getMessages() as $type => $messages)
        @foreach ($messages as $message)
            <div class="alert alert-{{ $type }} alert-dismissable" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                {{ $message }}
            </div>
        @endforeach
    @endforeach
    <h3 style="margin-top:0;">{{ trans('base.account.totp_header') }} <small>@if (Auth::user()->use_totp === 1){{ trans('strings.enabled') }}@else{{ trans('strings.disabled') }}@endif</small></h3><hr />
    @if (Auth::user()->use_totp === 1)
        <div class="panel panel-default">
            <div class="panel-heading">{{ trans('base.account.totp_disable') }}</div>
            <div class="panel-body">
                <p>{{ trans('base.account.totp_disable_help') }}</p>
                <br />
                <form action="/account/totp" method="post">
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon">{{ trans('base.account.totp_token') }}</span>
                            <input type="text" name="token" class="form-control">
                            <span class="input-group-btn">
                                {!! csrf_field() !!}
                                {{ method_field('DELETE') }}
                                <button class="btn btn-danger btn-sm" type="submit">{{ trans('base.account.totp_disable') }}</button>
                            </span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">{{ trans('base.account.totp_header') }}</h3>
            </div>
            <div class="panel-body">
                <p>{{ trans('base.account.totp_enable_help') }}</p>
                <div class="alert alert-info" style="margin-bottom: 0;">{{ trans('base.account.totp_apps') }}</div>
            </div>
        </div>
        <form action="#" id="do_totp" method="post">
            <div class="form-group">
                <div>
                    {!! csrf_field() !!}
                    <input type="submit" id="enable_totp" class="btn btn-success btn-sm" name="enable_totp" value="{{ trans('base.account.totp_enable') }}" />
                </div>
            </div>
        </form>
    @endif
    <div class="modal fade" id="openTOTP" tabindex="-1" role="dialog" aria-labelledby="openTOTP" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="#" method="post" id="totp_token_verify">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ trans('base.account.totp_qr') }}</h4>
                    </div>
                    <div class="modal-body" id="modal_insert_content">
                        <div class="row">
                            <div class="col-md-12" id="notice_box_totp" style="display:none;"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <center><span id="hide_img_load"><i class="fa fa-spinner fa-spin"></i> Loading QR Code...</span><img src="" id="qr_image_insert" style="display:none;"/><br /><code id="totp_secret_insert"></code></center>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">{{ trans('base.account.totp_checkpoint_help') }}</div>
                                <div class="form-group">
                                    <label class="control-label" for="totp_token">TOTP Token</label>
                                    {!! csrf_field() !!}
                                    <input class="form-control" type="text" id="totp_token" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary btn-sm" id="submit_action">{{ trans('strings.submit') }}</button>
                        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal" id="close_reload">{{ trans('strings.close') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find('a[href=\'/account/totp\']').addClass('active');

    $('#close_reload').click(function () {
        location.reload();
    });

    $('#do_totp').submit(function (event) {

        event.preventDefault();

        $.ajax({
            type: 'PUT',
            url: '/account/totp',
            headers: { 'X-CSRF-Token': '{{ csrf_token() }}' }
        }).done(function (data) {
            var image = new Image();
            image.src = data.qrImage;
            $(image).load(function () {
                $('#hide_img_load').slideUp(function () {
                    $('#qr_image_insert').attr('src', image.src).slideDown();
                });
            });
            $('#totp_secret_insert').html(data.secret);
            $('#openTOTP').modal('show');
        }).fail(function (jqXHR) {
            alert('An error occured while attempting to perform this action. Please try again.');
            console.log(jqXHR);
        });

    });
    $('#totp_token_verify').submit(function (event) {

        event.preventDefault();
        $('#submit_action').html('<i class="fa fa-spinner fa-spin"></i> {{ trans('strings.submit') }}').addClass('disabled');

        $.ajax({
            type: 'POST',
            url:'/account/totp',
            headers: { 'X-CSRF-Token': '{{ csrf_token() }}' },
            data: {
                token: $('#totp_token').val()
            }
        }).done(function (data) {
            $('#notice_box_totp').hide();
            if (data === 'true') {
                $('#notice_box_totp').html('<div class="alert alert-success">{{ trans('base.account.totp_enabled') }}</div>').slideDown();
            } else {
                $('#notice_box_totp').html('<div class="alert alert-danger">{{ trans('base.account.totp_enabled_error') }}</div>').slideDown();
            }
        }).fail(function (jqXHR) {
            alert('An error occured while attempting to perform this action. Please try again.');
            console.log(jqXHR);
        }).always(function () {
            $('#submit_action').html('{{ trans('strings.submit') }}').removeClass('disabled');
        });

    });
});
</script>
@endsection
