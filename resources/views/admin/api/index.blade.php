@extends('layouts.admin')

@section('title')
    应用 API
@endsection

@section('content-header')
    <h1>应用 API<small>通过 API 控制管理此面板的访问凭据.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li class="active">应用 API</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">凭证列表</h3>
                    <div class="box-tools">
                        <a href="{{ route('admin.api.new') }}" class="btn btn-sm btn-primary">新建</a>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tr>
                            <th>密钥 KEY</th>
                            <th>备注</th>
                            <th>上次使用于</th>
                            <th>创建于</th>
                            <th></th>
                        </tr>
                        @foreach($keys as $key)
                            <tr>
                                <td><code>{{ $key->identifier }}{{ decrypt($key->token) }}</code></td>
                                <td>{{ $key->memo }}</td>
                                <td>
                                    @if(!is_null($key->last_used_at))
                                        @datetimeHuman($key->last_used_at)
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td>@datetimeHuman($key->created_at)</td>
                                <td>
                                    <a href="#" data-action="revoke-key" data-attr="{{ $key->identifier }}">
                                        <i class="fa fa-trash-o text-danger"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            $('[data-action="revoke-key"]').click(function (event) {
                var self = $(this);
                event.preventDefault();
                swal({
                    type: 'error',
                    title: '删除 API 密钥',
                    text: '一旦此 API 密钥被删除，当前使用它的任何应用程序都将停止工作。',
                    showCancelButton: true,
                    allowOutsideClick: true,
                    closeOnConfirm: false,
                    confirmButtonText: '删除',
                    confirmButtonColor: '#d9534f',
                    showLoaderOnConfirm: true
                }, function () {
                    $.ajax({
                        method: 'DELETE',
                        url: '/admin/api/revoke/' + self.data('attr'),
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).done(function () {
                        swal({
                            type: 'success',
                            title: '',
                            text: '此 API 密钥已被删除.'
                        });
                        self.parent().parent().slideUp();
                    }).fail(function (jqXHR) {
                        console.error(jqXHR);
                        swal({
                            type: 'error',
                            title: '离了个大谱!',
                            text: '删除此 API 密钥时发生错误，此操作无法继续进行.'
                        });
                    });
                });
            });
        });
    </script>
@endsection
