<h4 class="nopad">/home/container{{ $directory['header'] }} &nbsp;<small><a href="/server/{{ $server->uuidShort }}/files/add/@if($directory['header'] !== '')?dir={{ $directory['header'] }}@endif" class="text-muted"><i class="fa fa-plus" data-toggle="tooltip" data-placement="top" title="Add New File(s)"></i></a></small></h4>
<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <th style="width:2%;text-align:center;"></th>
            <th style="width:45%">File Name</th>
            <th style="width:15%">Size</th>
            <th style="width:20%">Last Modified</th>
            <th style="width:20%;text-align:center;">Options</th>
        </tr>
    </thead>
    <tbody>
        @if (isset($directory['first']) && $directory['first'] === true)
            <tr>
                <td><i class="fa fa-folder-open" style="margin-left: 0.859px;"></i></td>
                <td><a href="/server/{{ $server->uuidShort }}/files" class="load_new">&larr;</a></a></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if (isset($directory['show']) && $directory['show'] === true)
            <tr>
                <td><i class="fa fa-folder-open" style="margin-left: 0.859px;"></i></td>
                <td><a href="/server/{{ $server->uuidShort }}/files?dir={{ $directory['link'] }}" class="load_new">&larr; {{ $directory['link_show'] }}</a></a></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @foreach ($folders as $folder)
            <tr>
                <td><i class="fa fa-folder-open" style="margin-left: 0.859px;"></i></td>
                <td><a href="/server/{{ $server->uuidShort }}/files?dir=/@if($folder['directory'] !== ''){{ $folder['directory'] }}/@endif{{ $folder['entry'] }}" class="load_new">{{ $folder['entry'] }}</a></td>
                <td>{{ $folder['size'] }}</td>
                <td>{{ date('m/d/y H:i:s', $folder['date']) }}</td>
                <td style="text-align:center;">
                    <div class="row" style="text-align:center;">
                        <div class="col-md-3 hidden-xs hidden-sm"></div>
                        <div class="col-md-3 hidden-xs hidden-sm">
                        </div>
                        <div class="col-md-3">
                            @can('delete-file', $server)
                                <a href="@if($folder['directory'] !== ''){{ $folder['directory'] }}/@endif{{ $folder['entry'] }}" class="delete_file"><span class="badge label-danger"><i class="fa fa-trash-o"></i></span></a>
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        @foreach ($files as $file)
            <tr>
                <td><i class="fa fa-file-text" style="margin-left: 2px;"></i></td>
                <td>
                    @if(in_array($file['extension'], $extensions))
                        @can('edit-file', $server)
                            <a href="/server/{{ $server->uuidShort }}/files/edit/@if($file['directory'] !== ''){{ $file['directory'] }}/@endif{{ $file['entry'] }}" class="edit_file">{{ $file['entry'] }}</a>
                        @else
                            {{ $file['entry'] }}
                        @endcan
                    @else
                        {{ $file['entry'] }}
                    @endif
                </td>
                <td>{{ $file['size'] }}</td>
                <td>{{ date('m/d/y H:i:s', $file['date']) }}</td>
                <td style="text-align:center;">
                    <div class="row" style="text-align:center;">
                        <div class="col-md-3 hidden-xs hidden-sm">
                        </div>
                        <div class="col-md-3 hidden-xs hidden-sm">
                            @can('download-file', $server)
                                <a href="/server/{{ $server->uuidShort }}/files/download/@if($file['directory'] !== ''){{ $file['directory'] }}/@endif{{ $file['entry'] }}"><span class="badge"><i class="fa fa-download"></i></span></a>
                            @endcan
                        </div>
                        <div class="col-md-3">
                            @can('delete-file', $server)
                                <a href="@if($file['directory'] !== ''){{ $file['directory'] }}/@endif{{ $file['entry'] }}" class="delete_file"><span class="badge label-danger"><i class="fa fa-trash-o"></i></span>
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
