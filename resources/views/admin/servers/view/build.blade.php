{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    服务器实例 — {{ $server->name }}: 构建设置
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>控制此服务器的分配和系统资源。</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li><a href="{{ route('admin.servers') }}">服务器实例</a></li>
        <li><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
        <li class="active">构建设置</li>
    </ol>
@endsection

@section('content')
@include('admin.servers.partials.navigation')
<div class="row">
    <form action="{{ route('admin.servers.view.build', $server->id) }}" method="POST">
        <div class="col-sm-5">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">资源管理</h3>
                </div>
                <div class="box-body">
                <div class="form-group">
                        <label for="cpu" class="control-label">CPU 限制</label>
                        <div class="input-group">
                            <input type="text" name="cpu" class="form-control" value="{{ old('cpu', $server->cpu) }}"/>
                            <span class="input-group-addon">%</span>
                        </div>
                        <p class="text-muted small">每 <em>虚拟</em> 内核 (线程) 于此系统都将视为 <code>100%</code>. 将此值设置为 <code>0</code> 将允许此服务器实例无限制使用CPU虚拟线程.</p>
                    </div>
                    <div class="form-group">
                        <label for="threads" class="control-label">CPU 核心</label>
                        <div>
                            <input type="text" name="threads" class="form-control" value="{{ old('threads', $server->threads) }}"/>
                        </div>
                        <p class="text-muted small"><strong>高级:</strong> 输入此进程可以在其上运行的特定 CPU 内核，或留空以允许所有内核。 这可以是单个数字，也可以是逗号分隔的列表. 例如: <code>0</code>, <code>0-1,3</code>, 或者 <code>0,1,3,4</code>.</p>
                    </div>
                    <div class="form-group">
                        <label for="memory" class="control-label">分配内存</label>
                        <div class="input-group">
                            <input type="text" name="memory" data-multiplicator="true" class="form-control" value="{{ old('memory', $server->memory) }}"/>
                            <span class="input-group-addon">MB</span>
                        </div>
                        <p class="text-muted small">此服务器实例允许的最大内存使用量。 将此设置为 <code>0</code> 将不限制此服务器实例内存使用。</p>
                    </div>
                    <div class="form-group">
                        <label for="swap" class="control-label">分配交换内存</label>
                        <div class="input-group">
                            <input type="text" name="swap" data-multiplicator="true" class="form-control" value="{{ old('swap', $server->swap) }}"/>
                            <span class="input-group-addon">MB</span>
                        </div>
                        <p class="text-muted small">将此设置为 <code>0</code> 将禁用此服务器实例的交换内存. 将此设置为 <code>-1</code> 将允许此服务器实例使用无限制交换内存.</p>
                    </div>
                    <div class="form-group">
                        <label for="cpu" class="control-label">存储空间限制</label>
                        <div class="input-group">
                            <input type="text" name="disk" class="form-control" value="{{ old('disk', $server->disk) }}"/>
                            <span class="input-group-addon">MB</span>
                        </div>
                        <p class="text-muted small">如果此服务器实例使用的空间超过此数量，则将不允许它启动。 如果服务器实例在运行时超过此限制，它将安全停止并锁定，直到有足够的可用空间。 调成 <code>0</code> 允许此服务器实例无限制使用存储空间.</p>
                    </div>
                    <div class="form-group">
                        <label for="io" class="control-label">IO 优先级</label>
                        <div>
                            <input type="text" name="io" class="form-control" value="{{ old('io', $server->io) }}"/>
                        </div>
                        <p class="text-muted small"><strong>高级</strong>: 此服务器实例相对于其他 <em>运行中</em> 服务器实例的 IO 性能 . 此值应介于 <code>10</code> 至 <code>1000</code>.</p>
                    </div>
                    <div class="form-group">
                        <label for="cpu" class="control-label">OOM Killer</label>
                        <div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" id="pOomKillerEnabled" value="0" name="oom_disabled" @if(!$server->oom_disabled)checked @endif>
                                <label for="pOomKillerEnabled">启用</label>
                            </div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pOomKillerDisabled" value="1" name="oom_disabled" @if($server->oom_disabled)checked @endif>
                                <label for="pOomKillerDisabled">禁用</label>
                            </div>
                            <p class="text-muted small">
                                启用 OOM killer 可能会导致服务器程序异常.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-7">
            <div class="row">
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">应用程序功能限制</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-xs-6">
                                    <label for="database_limit" class="control-label">数据库限制</label>
                                    <div>
                                        <input type="text" name="database_limit" class="form-control" value="{{ old('database_limit', $server->database_limit) }}"/>
                                    </div>
                                    <p class="text-muted small">允许用户为此服务器创建的数据库总数.</p>
                                </div>
                                <div class="form-group col-xs-6">
                                    <label for="allocation_limit" class="control-label">网络分配限制</label>
                                    <div>
                                        <input type="text" name="allocation_limit" class="form-control" value="{{ old('allocation_limit', $server->allocation_limit) }}"/>
                                    </div>
                                    <p class="text-muted small">允许用户为此服务器创建的网络分配总数。</p>
                                </div>
                                <div class="form-group col-xs-6">
                                    <label for="backup_limit" class="control-label">备份限制</label>
                                    <div>
                                        <input type="text" name="backup_limit" class="form-control" value="{{ old('backup_limit', $server->backup_limit) }}"/>
                                    </div>
                                    <p class="text-muted small">可以为此服务器创建的备份总数。</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">网络分配</h3>
                        </div>
                        <div class="box-body">
                            <div class="form-group">
                                <label for="pAllocation" class="control-label">端口</label>
                                <select id="pAllocation" name="allocation_id" class="form-control">
                                    @foreach ($assigned as $assignment)
                                        <option value="{{ $assignment->id }}"
                                            @if($assignment->id === $server->allocation_id)
                                                selected="selected"
                                            @endif
                                        >{{ $assignment->alias }}:{{ $assignment->port }}</option>
                                    @endforeach
                                </select>
                                <p class="text-muted small">将用于此服务器的默认连接地址。</p>
                            </div>
                            <div class="form-group">
                                <label for="pAddAllocations" class="control-label">分配额外端口</label>
                                <div>
                                    <select name="add_allocations[]" class="form-control" multiple id="pAddAllocations">
                                        @foreach ($unassigned as $assignment)
                                            <option value="{{ $assignment->id }}">{{ $assignment->alias }}:{{ $assignment->port }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <p class="text-muted small">请注意，由于软件限制，您不能将不同 IP 上的相同端口分配给同一台服务器.</p>
                            </div>
                            <div class="form-group">
                                <label for="pRemoveAllocations" class="control-label">移除额外端口</label>
                                <div>
                                    <select name="remove_allocations[]" class="form-control" multiple id="pRemoveAllocations">
                                        @foreach ($assigned as $assignment)
                                            <option value="{{ $assignment->id }}">{{ $assignment->alias }}:{{ $assignment->port }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <p class="text-muted small">只需从上面的列表中选择您要删除的端口。 如果您想在已使用的不同 IP 上分配一个端口，您可以从左侧选择它并在此处将其删除.</p>
                            </div>
                        </div>
                        <div class="box-footer">
                            {!! csrf_field() !!}
                            <button type="submit" class="btn btn-primary pull-right">更新构建设置</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('#pAddAllocations').select2();
    $('#pRemoveAllocations').select2();
    $('#pAllocation').select2();
    </script>
@endsection
