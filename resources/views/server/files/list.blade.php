{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}

<div class="box-header with-border">
    <h3 class="box-title">/home/container{{ $directory['header'] }}</h3>
    <div class="box-tools">
        <a href="/server/{{ $server->uuidShort }}/files/add/@if($directory['header'] !== '')?dir={{ $directory['header'] }}@endif">
            <button class="btn btn-success btn-sm btn-icon">
                New File <i class="fa fa-fw fa-file-text-o"></i>
            </button>
        </a>
        <button class="btn btn-sm btn-success btn-icon" data-action="add-folder">
            New Folder <i class="fa fa-fw fa-folder-open-o"></i>
        </button>
        <label class="btn btn-primary btn-sm btn-icon">
            Upload <i class="fa fa-fw fa-upload"></i><input type="file" id="files_touch_target" class="hidden">
        </label>
        <div class="btn-group hidden-xs">
            <button type="button" id="mass_actions" class="btn btn-sm btn-default dropdown-toggle disabled" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                @lang('server.files.mass_actions') <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-massactions">
                <li><a href="#" id="selective-deletion" data-action="selective-deletion">@lang('server.files.delete') <i class="fa fa-fw fa-trash-o"></i></a></li>
            </ul>
        </div>
    </div>
</div>
<div class="box-body table-responsive no-padding">
    <table class="table table-hover" id="file_listing" data-current-dir="{{ rtrim($directory['header'], '/') . '/' }}">
        <thead>
            <tr>
                <th class="middle min-size">
                    <input type="checkbox" class="select-all-files hidden-xs" data-action="selectAll"><i class="fa fa-refresh muted muted-hover use-pointer" data-action="reload-files" style="font-size:14px;"></i>
                </th>
                <th>@lang('server.files.file_name')</th>
                <th class="hidden-xs">@lang('server.files.size')</th>
                <th class="hidden-xs">@lang('server.files.last_modified')</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="append_files_to">
            @if (isset($directory['first']) && $directory['first'] === true)
                <tr data-type="disabled">
                    <td class="middle min-size"><i class="fa fa-folder" style="margin-left: 0.859px;"></i></td>
                    <td><a href="/server/{{ $server->uuidShort }}/files" data-action="directory-view">&larr;</a></td>
                    <td class="hidden-xs"></td>
                    <td class="hidden-xs"></td>
                    <td></td>
                </tr>
            @endif
            @if (isset($directory['show']) && $directory['show'] === true)
                <tr data-type="disabled">
                    <td class="middle min-size"><i class="fa fa-folder" style="margin-left: 0.859px;"></i></td>
                    <td data-name="{{ rawurlencode($directory['link']) }}">
                        <a href="/server/{{ $server->uuidShort }}/files" data-action="directory-view">&larr; {{ $directory['link_show'] }}</a>
                    </td>
                    <td class="hidden-xs"></td>
                    <td class="hidden-xs"></td>
                    <td></td>
                </tr>
            @endif
            @foreach ($folders as $folder)
                <tr data-type="folder">
                    <td class="middle min-size" data-identifier="type">
                        <input type="checkbox" class="select-folder hidden-xs" data-action="addSelection"><i class="fa fa-folder" style="margin-left: 0.859px;"></i>
                    </td>
                    <td data-identifier="name" data-name="{{ rawurlencode($folder['entry']) }}" data-path="@if($folder['directory'] !== ''){{ rawurlencode($folder['directory']) }}@endif/">
                        <a href="/server/{{ $server->uuidShort }}/files" data-action="directory-view">{{ $folder['entry'] }}</a>
                    </td>
                    <td data-identifier="size" class="hidden-xs">{{ $folder['size'] }}</td>
                    <td data-identifier="modified" class="hidden-xs">
                        <?php $carbon = Carbon::createFromTimestamp($folder['date'])->timezone(config('app.timezone')); ?>
                        @if($carbon->diffInMinutes(Carbon::now()) > 60)
                            {{ $carbon->format('m/d/y H:i:s') }}
                        @elseif($carbon->diffInSeconds(Carbon::now()) < 5 || $carbon->isFuture())
                            <em>@lang('server.files.seconds_ago')</em>
                        @else
                            {{ $carbon->diffForHumans() }}
                        @endif
                    </td>
                    <td class="min-size">
                        <button class="btn btn-xxs btn-default disable-menu-hide" data-action="toggleMenu" style="padding:2px 6px 0px;"><i class="fa fa-ellipsis-h disable-menu-hide"></i></button>
                    </td>
                </tr>
            @endforeach
            @foreach ($files as $file)
                <tr data-type="file" data-mime="{{ $file['mime'] }}">
                    <td class="middle min-size" data-identifier="type"><input type="checkbox" class="select-file hidden-xs" data-action="addSelection">
                        {{--  oh boy --}}
                        @if(in_array($file['mime'], [
                            'application/x-7z-compressed',
                            'application/zip',
                            'application/x-compressed-zip',
                            'application/x-tar',
                            'application/x-gzip',
                            'application/x-bzip',
                            'application/x-bzip2',
                            'application/java-archive'
                        ]))
                            <i class="fa fa-file-archive-o" style="margin-left: 2px;"></i>
                        @elseif(in_array($file['mime'], [
                            'application/json',
                            'application/javascript',
                            'application/xml',
                            'application/xhtml+xml',
                            'text/xml',
                            'text/css',
                            'text/html',
                            'text/x-perl',
                            'text/x-shellscript'
                        ]))
                            <i class="fa fa-file-code-o" style="margin-left: 2px;"></i>
                        @elseif(starts_with($file['mime'], 'image'))
                            <i class="fa fa-file-image-o" style="margin-left: 2px;"></i>
                        @elseif(starts_with($file['mime'], 'video'))
                            <i class="fa fa-file-video-o" style="margin-left: 2px;"></i>
                        @elseif(starts_with($file['mime'], 'video'))
                            <i class="fa fa-file-audio-o" style="margin-left: 2px;"></i>
                        @elseif(starts_with($file['mime'], 'application/vnd.ms-powerpoint'))
                            <i class="fa fa-file-powerpoint-o" style="margin-left: 2px;"></i>
                        @elseif(in_array($file['mime'], [
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
                            'application/msword'
                        ]) || starts_with($file['mime'], 'application/vnd.ms-word'))
                            <i class="fa fa-file-word-o" style="margin-left: 2px;"></i>
                        @elseif(in_array($file['mime'], [
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
                        ]) || starts_with($file['mime'], 'application/vnd.ms-excel'))
                            <i class="fa fa-file-excel-o" style="margin-left: 2px;"></i>
                        @elseif($file['mime'] === 'application/pdf')
                            <i class="fa fa-file-pdf-o" style="margin-left: 2px;"></i>
                        @else
                            <i class="fa fa-file-text-o" style="margin-left: 2px;"></i>
                        @endif
                    </td>
                    <td data-identifier="name" data-name="{{ rawurlencode($file['entry']) }}" data-path="@if($file['directory'] !== ''){{ rawurlencode($file['directory']) }}@endif/">
                        @if(in_array($file['mime'], $editableMime))
                            @can('edit-files', $server)
                                <a href="/server/{{ $server->uuidShort }}/files/edit/@if($file['directory'] !== ''){{ rawurlencode($file['directory']) }}/@endif{{ rawurlencode($file['entry']) }}" class="edit_file">{{ $file['entry'] }}</a>
                            @else
                                {{ $file['entry'] }}
                            @endcan
                        @else
                            {{ $file['entry'] }}
                        @endif
                    </td>
                    <td data-identifier="size" class="hidden-xs">{{ $file['size'] }}</td>
                    <td data-identifier="modified" class="hidden-xs">
                        <?php $carbon = Carbon::createFromTimestamp($file['date'])->timezone(config('app.timezone')); ?>
                        @if($carbon->diffInMinutes(Carbon::now()) > 60)
                            {{ $carbon->format('m/d/y H:i:s') }}
                        @elseif($carbon->diffInSeconds(Carbon::now()) < 5 || $carbon->isFuture())
                            <em>@lang('server.files.seconds_ago')</em>
                        @else
                            {{ $carbon->diffForHumans() }}
                        @endif
                    </td>
                    <td class="min-size">
                        <button class="btn btn-xxs btn-default disable-menu-hide" data-action="toggleMenu" style="padding:2px 6px 0px;"><i class="fa fa-ellipsis-h disable-menu-hide"></i></button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
