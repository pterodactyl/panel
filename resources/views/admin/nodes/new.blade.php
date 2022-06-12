{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    节点服务器 &rarr; 新建
@endsection

@section('content-header')
    <h1>新节点服务器<small>在本地或远程主机创建面板使用的新节点服务器.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li><a href="{{ route('admin.nodes') }}">节点服务器</a></li>
        <li class="active">新建</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.nodes.new') }}" method="POST">
    <div class="row">
        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">基础信息</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pName" class="form-label">名称</label>
                        <input type="text" name="name" id="pName" class="form-control" value="{{ old('name') }}"/>
                        <p class="text-muted small">字符限制: <code>a-zA-Z0-9_.-</code> 与 <code>[空格]</code> (最少 1, 最多 100 字符).</p>
                    </div>
                    <div class="form-group">
                        <label for="pDescription" class="form-label">描述</label>
                        <textarea name="description" id="pDescription" rows="4" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="pLocationId" class="form-label">节点服务器组</label>
                        <select name="location_id" id="pLocationId">
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ $location->id != old('location_id') ?: 'selected' }}>{{ $location->short }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">节点可见性</label>
                        <div>
                            <div class="radio radio-success radio-inline">

                                <input type="radio" id="pPublicTrue" value="1" name="public" checked>
                                <label for="pPublicTrue"> 公开 </label>
                            </div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" id="pPublicFalse" value="0" name="public">
                                <label for="pPublicFalse"> 私人 </label>
                            </div>
                        </div>
                        <p class="text-muted small">将节点设为 <code>私人</code> 将无法使用节点自动部署的功能.
                    </div>
                    <div class="form-group">
                        <label for="pFQDN" class="form-label">域名</label>
                        <input type="text" name="fqdn" id="pFQDN" class="form-control" value="{{ old('fqdn') }}"/>
                        <p class="text-muted small">请输入节点服务器域名 (例如 <code>node.example.com</code>) 用来连接至节点服务器主机. IP 地址 <em>仅能</em> 在不使用SSL连接的情况下填写使用.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">与面板前端以 SSL 通信</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pSSLTrue" value="https" name="scheme" checked>
                                <label for="pSSLTrue"> 使用 SSL 通信</label>
                            </div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" id="pSSLFalse" value="http" name="scheme" @if(request()->isSecure()) disabled @endif>
                                <label for="pSSLFalse"> 使用 HTTP 通信 (无 SSL)</label>
                            </div>
                        </div>
                        @if(request()->isSecure())
                            <p class="text-danger small">您的面板当前配置为使用 SSL 安全连接。 为了让浏览器连接到您的节点，其 <strong>必须</strong> 使用 SSL 连接.</p>
                        @else
                            <p class="text-muted small">在大多数情况下，您应该选择使用 SSL 连接。 如果使用 IP 地址或者您根本不想使用 SSL，请选择 HTTP 连接。( 不安全 )</p>
                        @endif
                    </div>
                    <div class="form-group">
                        <label class="form-label">通过代理</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pProxyFalse" value="0" name="behind_proxy" checked>
                                <label for="pProxyFalse"> 不通过代理 </label>
                            </div>
                            <div class="radio radio-info radio-inline">
                                <input type="radio" id="pProxyTrue" value="1" name="behind_proxy">
                                <label for="pProxyTrue"> 通过代理 </label>
                            </div>
                        </div>
                        <p class="text-muted small">如果您在 Cloudflare 等代理CDN运行守护程序，请选择此选项以使守护程序在启动时跳过查找证书。</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">设置</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="pDaemonBase" class="form-label">守护程序服务器文件目录</label>
                            <input type="text" name="daemonBase" id="pDaemonBase" class="form-control" value="/var/lib/pterodactyl/volumes" />
                            <p class="text-muted small">输入存储服务器使用的文件目录. <strong>如果您使用 OVH，您应该检查您的分区方案。 你可能需要让 <code>/home/daemon-data</code> 有足够的空间.</strong></p>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pMemory" class="form-label">总运行内存容量</label>
                            <div class="input-group">
                                <input type="text" name="memory" data-multiplicator="true" class="form-control" id="pMemory" value="{{ old('memory') }}"/>
                                <span class="input-group-addon">MB</span>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pMemoryOverallocate" class="form-label">内存过额分配</label>
                            <div class="input-group">
                                <input type="text" name="memory_overallocate" class="form-control" id="pMemoryOverallocate" value="{{ old('memory_overallocate') }}"/>
                                <span class="input-group-addon">%</span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <p class="text-muted small">输入可用于新服务器的内存总量。 如果您希望允许过度分配内存，请输入您希望允许的百分比。 要禁用检查过度分配，请输入 <code>-1</code> 于此处. 输入 <code>0</code> 的话，如果服务器实例内存总量超过节点服务器最大内存总量，将阻止创建新服务器.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="pDisk" class="form-label">总存储容量</label>
                            <div class="input-group">
                                <input type="text" name="disk" data-multiplicator="true" class="form-control" id="pDisk" value="{{ old('disk') }}"/>
                                <span class="input-group-addon">MB</span>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pDiskOverallocate" class="form-label">存储空间过额分配</label>
                            <div class="input-group">
                                <input type="text" name="disk_overallocate" class="form-control" id="pDiskOverallocate" value="{{ old('disk_overallocate') }}"/>
                                <span class="input-group-addon">%</span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <p class="text-muted small">输入可用于新服务器的磁盘空间总量。 如果您希望允许过度分配磁盘空间，请输入您希望允许的百分比。 要禁用检查过度分配，请输入 <code>-1</code> 于此处. 输入 <code>0</code> 的话，如果服务器实例存储空间总用量超过节点服务器最大存储空间总量，将阻止创建新服务器.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="pDaemonListen" class="form-label">守护进程端口</label>
                            <input type="text" name="daemonListen" class="form-control" id="pDaemonListen" value="8080" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pDaemonSFTP" class="form-label">守护进程 SFTP 端口</label>
                            <input type="text" name="daemonSFTP" class="form-control" id="pDaemonSFTP" value="2022" />
                        </div>
                        <div class="col-md-12">
                            <p class="text-muted small">守护进程运行自己的 SFTP 管理容器，并且不使用主物理服务器上的 SSHd 进程。 <Strong>不要使用为物理服务器的 SSH 进程分配的相同端口。</strong> 如果您将在 CloudFlare 后面运行守护程序&reg; 您应该将守护程序端口设置为 <code>8443</code> 允许通过 SSL 进行 websocket 代理.</p>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-success pull-right">创建节点服务器</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#pLocationId').select2();
    </script>
@endsection
