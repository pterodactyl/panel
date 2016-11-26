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
    Node List
@endsection

@section('scripts')
    @parent
    {!! Theme::css('css/vendor/fontawesome/animation.min.css') !!}
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li class="active">Nodes</li>
    </ul>
    <h3>All Nodes</h3><hr />
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th></th>
                <th>Name</th>
                <th>Location</th>
                <th class="hidden-xs">Memory</th>
                <th class="hidden-xs">Disk</th>
                <th class="text-center hidden-xs">Servers</th>
                <th class="text-center">SSL</th>
                <th class="text-center hidden-xs">Public</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($nodes as $node)
                <tr>
                    <td class="text-center text-muted left-icon" data-action="ping" data-location="{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}"><i class="fa fa-fw fa-refresh fa-spin"></i></td>
                    <td><a href="/admin/nodes/view/{{ $node->id }}">{{ $node->name }}</td>
                    <td>{{ $node->a_locationName }}</td>
                    <td class="hidden-xs">{{ $node->memory }} MB</td>
                    <td class="hidden-xs">{{ $node->disk }} MB</td>
                    <td class="text-center hidden-xs">{{ $node->a_serverCount }}</td>
                    <td class="text-center" style="color:{{ ($node->scheme === 'https') ? '#50af51' : '#d9534f' }}"><i class="fa fa-{{ ($node->scheme === 'https') ? 'lock' : 'unlock' }}"></i></td>
                    <td class="text-center hidden-xs"><i class="fa fa-{{ ($node->public === 1) ? 'eye' : 'eye-slash' }}"></i></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="row">
        <div class="col-md-12 text-center">{!! $nodes->render() !!}</div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/nodes']").addClass('active');
    (function pingNodes() {
        $('td[data-action="ping"]').each(function(i, element) {
            $.ajax({
                type: 'GET',
                url: $(element).data('location'),
                headers: {
                    'X-Access-Token': '{{ $node->daemonSecret }}'
                },
                timeout: 5000
            }).done(function (data) {
                $(element).find('i').tooltip({
                    title: 'v' + data.version,
                });
                $(element).removeClass('text-muted').find('i').removeClass().addClass('fa fa-fw fa-heartbeat faa-pulse animated').css('color', '#50af51');
            }).fail(function () {
                $(element).removeClass('text-muted').find('i').removeClass().addClass('fa fa-fw fa-heart-o').css('color', '#d9534f');
            }).always(function () {
                setTimeout(pingNodes, 10000);
            });
        });
    })();
}
</script>
@endsection
