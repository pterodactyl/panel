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
@extends('layouts.admin')

@section('title')
    Administration
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <ul class="breadcrumb">
            <li class="active">Admin Control</li>
        </ul>
        <h3 class="nopad">Pterodactyl Admin Control Panel</h3><hr />
        @if (Version::isLatestPanel())
            <div class="alert alert-success">You are running Pterodactyl Panel version <code>{{ Version::getCurrentPanel() }}</code>. Your panel is up-to-date!</div>
        @else
            <div class="alert alert-danger">
                Your panel is <strong>not up-to-date!</strong> The latest version is <a href="https://github.com/Pterodactyl/Panel/releases/v{{ Version::getPanel() }}" target="_blank"><code>{{ Version::getPanel() }}</code></a> and you are currently running version <code>{{ Version::getCurrentPanel() }}</code>.
            </div>
        @endif
    </div>
</div>
<div class="row">
    <div class="col-xs-4 text-center">
        <a href="https://discord.gg/QRDZvVm"><button class="btn btn-sm btn-warning" style="width:100%;"><i class="fa fa-fw fa-support"></i> Get Help <small>(via Discord)</small></button></a>
    </div>
    <div class="col-xs-4 text-center">
        <a href="https://docs.pterodactyl.io"><button class="btn btn-sm btn-default" style="width:100%;"><i class="fa fa-fw fa-link"></i> Documentation</button></a>
    </div>
    <div class="col-xs-4 text-center">
        <a href="https://github.com/Pterodactyl/Panel"><button class="btn btn-sm btn-default" style="width:100%;"><i class="fa fa-fw fa-support"></i> Github</button></a>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin']").addClass('active');
});
</script>
@endsection
