@extends('layouts.admin')

@section('title')
    Settings | Terms of Services
@endsection

@section('content-header')
    <h1>Terms of Services Settings <small>Setup your terms of services.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Terms of Services</li>
    </ol>
@endsection

@section('content')
    @include('admin.shop.settings.partials.navigation')

    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Terms of Services</h3>
                </div>
                <form method="post" action="{{ route('admin.shop.settings.tos') }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="tos_url">TOS Url</label>
                            <input type="text" class="form-control" name="tos_url" id="tos_url" value="{{ old('tos_url', $tos_url) }}" placeholder="https://myhost.tld/mytos.pdf">
                        </div>
                        <hr>
                        <h3>Or: <small>If you set TOS Url, the message won't be shown.</small></h3>
                        <hr>
                        <div class="form-group">
                            <textarea name="tos" id="tos">{!! $tos !!}</textarea>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script src="//cdn.tiny.cloud/1/{{ $tinyLicense }}/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#tos',
            plugins: 'advlist autolink lists link image charmap print preview hr anchor pagebreak code',
            toolbar_mode: 'floating',
            height: '500px',
            skin: "oxide-dark",
            content_css: "dark",
        });
    </script>
@endsection
