@extends('layouts.admin')

@section('title')
    应用 API
@endsection

@section('content-header')
    <h1>应用 API<small>创建新的应用程序 API 密钥.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li><a href="{{ route('admin.api.index') }}">应用 API</a></li>
        <li class="active">新凭据</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <form method="POST" action="{{ route('admin.api.new') }}">
            <div class="col-sm-8 col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">选择权限</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover">
                            @foreach($resources as $resource)
                                <tr>
                                    <td class="col-sm-3 strong">{{ str_replace('_', ' ', title_case($resource)) }}</td>
                                    <td class="col-sm-3 radio radio-primary text-center">
                                        <input type="radio" id="r_{{ $resource }}" name="r_{{ $resource }}" value="{{ $permissions['r'] }}">
                                        <label for="r_{{ $resource }}">只读</label>
                                    </td>
                                    <td class="col-sm-3 radio radio-primary text-center">
                                        <input type="radio" id="rw_{{ $resource }}" name="r_{{ $resource }}" value="{{ $permissions['rw'] }}">
                                        <label for="rw_{{ $resource }}">可读 &amp; 可写</label>
                                    </td>
                                    <td class="col-sm-3 radio text-center">
                                        <input type="radio" id="n_{{ $resource }}" name="r_{{ $resource }}" value="{{ $permissions['n'] }}" checked>
                                        <label for="n_{{ $resource }}">无权限</label>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 col-xs-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="control-label" for="memoField">描述 <span class="field-required"></span></label>
                            <input id="memoField" type="text" name="memo" class="form-control">
                        </div>
                        <p class="text-muted">一旦您分配了权限并创建了这组凭据，您将无法返回并对其进行编辑。 如果您需要在以后进行更改，则需要创建一组新的凭据.</p>
                    </div>
                    <div class="box-footer">
                        {{ csrf_field() }}
                        <button type="submit" class="btn btn-success btn-sm pull-right">创建凭据</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    </script>
@endsection
