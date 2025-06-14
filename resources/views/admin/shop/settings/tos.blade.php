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
                            <textarea name="tos" id="tos">
                                {!! $tos !!}
                            </textarea>
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
    <script src="https://cdn.tiny.cloud/1/xxgyxwiaqqglhni5qardovr11rmsswgfu5ahsnrtcphvyyun/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: 'textarea',
            plugins: [
            // Core editing features
            'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount',
            // Your account includes a free trial of TinyMCE premium features
            // Try the most popular premium features until Jun 28, 2025:
            'checklist', 'mediaembed', 'casechange', 'formatpainter', 'pageembed', 'a11ychecker', 'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'editimage', 'advtemplate', 'ai', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags', 'autocorrect', 'typography', 'inlinecss', 'markdown','importword', 'exportword', 'exportpdf'
            ],
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
            tinycomments_mode: 'embedded',
            tinycomments_author: 'Author name',
            mergetags_list: [
            { value: 'First.Name', title: 'First Name' },
            { value: 'Email', title: 'Email' },
            ],
            ai_request: (request, respondWith) => respondWith.string(() => Promise.reject('See docs to implement AI Assistant')),
        });
    </script>
@endsection
