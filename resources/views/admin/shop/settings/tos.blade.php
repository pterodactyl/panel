@extends('layouts.admin')

@section('title')
    Settings | Terms of Service
@endsection

@section('content-header')
    <h1>Terms of Service Settings <small>Configure your Terms of Service settings</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Terms of Service</li>
    </ol>
@endsection

@section('content')
    @include('admin.shop.settings.partials.navigation')

    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Terms of Service</h3>
                </div>
                <form method="post" action="{{ route('admin.shop.settings.tos') }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="tos_url">Terms of Service URL</label>
                            <input type="text" class="form-control" name="tos_url" id="tos_url" value="{{ old('tos_url', $tos_url) }}" placeholder="https://myhost.tld/mytos.pdf">
                        </div>
                        <hr>
                        <h3><small>If you set a Terms of Service URL, the below text will not be displayed.</small></h3>
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
