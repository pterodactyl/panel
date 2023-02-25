@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Servers') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                href="{{ route('servers.index') }}">{{ __('Servers') }}</a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    <!-- MAIN CONTENT -->
    <section class="content">
        <div class="container-fluid">

            <!-- CUSTOM CONTENT -->
            <div class="d-flex justify-content-md-start justify-content-center mb-3 ">
                <a @if (Auth::user()->Servers->count() >= Auth::user()->server_limit)
                    disabled="disabled" title="Server limit reached!"
                    @endif href="{{ route('servers.create') }}"
                    class="btn
                    @if (Auth::user()->Servers->count() >= Auth::user()->server_limit) disabled
                    @endif btn-primary"><i
                        class="fa fa-plus mr-2"></i>
                    {{ __('Create Server') }}
                </a>
                @if (Auth::user()->Servers->count() > 0&&!empty(config('SETTINGS::MISC:PHPMYADMIN:URL')))
                    <a 
                        href="{{ config('SETTINGS::MISC:PHPMYADMIN:URL') }}" target="_blank"
                        class="btn btn-secondary ml-2"><i title="manage"
                        class="fas fa-database mr-2"></i><span>{{ __('Database') }}</span>
                    </a>
                @endif
            </div>

            <div class="row d-flex flex-row justify-content-center justify-content-md-start">
                @foreach ($servers as $server)
                    @if($server->location&&$server->node&&$server->nest&&$server->egg)
                    <div class="col-xl-3 col-lg-5 col-md-6 col-sm-6 col-xs-12 card pr-0 pl-0 ml-sm-2 mr-sm-3"
                        style="max-width: 350px">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mt-1">{{ $server->name }}
                                </h5>
                                <div class="card-tools mt-1">
                                    <div class="dropdown no-arrow">
                                        <a href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-white-50"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                            aria-labelledby="dropdownMenuLink">
                                            @if (!empty(config('SETTINGS::MISC:PHPMYADMIN:URL')))
                                                <a href="{{ config('SETTINGS::MISC:PHPMYADMIN:URL') }}"
                                                    class="dropdown-item text-info" target="__blank"><i title="manage"
                                                        class="fas fa-database mr-2"></i><span>{{ __('Database') }}</span></a>
                                            @endif
                                            <div class="dropdown-divider"></div>
                                            <span class="dropdown-item"><i title="Created at"
                                                    class="fas fa-sync-alt mr-2"></i><span>{{ $server->created_at->isoFormat('LL') }}</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="container mt-1">
                                <div class="row mb-3">
                                    <div class="col my-auto">{{ __('Status') }}:</div>
                                    <div class="col-7 my-auto">
                                        @if($server->suspended)
                                            <span class="badge badge-danger">{{ __('Suspended') }}</span>
                                        @elseif($server->cancelled)
                                            <span class="badge badge-warning">{{ __('Cancelled') }}</span>
                                        @else
                                            <span class="badge badge-success">{{ __('Active') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5">
                                        {{ __('Location') }}:
                                    </div>
                                    <div class="col-7 d-flex justify-content-between align-items-center">
                                        <span class="">{{ $server->location }}</span>
                                        <i data-toggle="popover" data-trigger="hover"
                                            data-content="{{ __('Node') }}: {{ $server->node }}"
                                            class="fas fa-info-circle"></i>
                                    </div>

                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5 ">
                                            {{ __('Software') }}:
                                        </div>
                                        <div class="col-7 text-wrap">
                                            <span>{{ $server->nest }}</span>
                                        </div>

                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 ">
                                        {{ __('Specification') }}:
                                    </div>
                                    <div class="col-7 text-wrap">
                                        <span>{{ $server->egg }}</span>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 ">
                                        {{ __('Resource plan') }}:
                                    </div>
                                    <div class="col-7 text-wrap d-flex justify-content-between align-items-center">
                                        <span>{{ $server->product->name }}
                                        </span>
                                        <i data-toggle="popover" data-trigger="hover" data-html="true"
                                            data-content="{{ __('CPU') }}: {{ $server->product->cpu / 100 }} {{ __('vCores') }} <br/>{{ __('RAM') }}: {{ $server->product->memory }} MB <br/>{{ __('Disk') }}: {{ $server->product->disk }} MB <br/>{{ __('Backups') }}: {{ $server->product->backups }} <br/> {{ __('MySQL Databases') }}: {{ $server->product->databases }} <br/> {{ __('Allocations') }}: {{ $server->product->allocations }} <br/> {{ __('Billing Period') }}: {{$server->product->billing_period}}"
                                            class="fas fa-info-circle"></i>
                                    </div>
                                </div>

                                <div class="row mb-4 ">
                                    <div class="col-5 word-break" style="hyphens: auto">
                                        {{ __('Next Billing Cycle') }}:
                                    </div>
                                    <div class="col-7 d-flex text-wrap align-items-center">
                                        <span>
                                        @if ($server->suspended)
                                            -
                                        @else
                                            @switch($server->product->billing_period)
                                                @case('monthly')
                                                    {{ \Carbon\Carbon::parse($server->last_billed)->addMonth()->toDayDateTimeString(); }}
                                                    @break
                                                @case('weekly')
                                                    {{ \Carbon\Carbon::parse($server->last_billed)->addWeek()->toDayDateTimeString(); }}
                                                    @break
                                                @case('daily')
                                                    {{ \Carbon\Carbon::parse($server->last_billed)->addDay()->toDayDateTimeString(); }}
                                                    @break
                                                @case('hourly')
                                                    {{ \Carbon\Carbon::parse($server->last_billed)->addHour()->toDayDateTimeString(); }}
                                                    @break
                                                @case('quarterly')
                                                    {{ \Carbon\Carbon::parse($server->last_billed)->addMonths(3)->toDayDateTimeString(); }}
                                                    @break
                                                @case('half-annually')
                                                    {{ \Carbon\Carbon::parse($server->last_billed)->addMonths(6)->toDayDateTimeString(); }}
                                                    @break
                                                @case('annually')
                                                    {{ \Carbon\Carbon::parse($server->last_billed)->addYear()->toDayDateTimeString(); }}
                                                    @break
                                                @default
                                                    {{ __('Unknown') }}
                                            @endswitch
                                        @endif
                                        </span>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <div class="col-4">
                                        {{ __('Price') }}:
                                        <span class="text-muted">
                                            ({{ CREDITS_DISPLAY_NAME }})
                                        </span>
                                    </div>
                                    <div class="col-8 text-center">
                                        <div class="text-muted">
                                        @if($server->product->billing_period == 'monthly')
                                            {{ __('per Month') }}
                                        @elseif($server->product->billing_period == 'half-annually')
                                            {{ __('per 6 Months') }}
                                        @elseif($server->product->billing_period == 'quarterly')
                                            {{ __('per 3 Months') }}
                                        @elseif($server->product->billing_period == 'annually')
                                            {{ __('per Year') }}
                                        @elseif($server->product->billing_period == 'weekly')
                                            {{ __('per Week') }}
                                        @elseif($server->product->billing_period == 'daily')
                                            {{ __('per Day') }}
                                        @elseif($server->product->billing_period == 'hourly')
                                            {{ __('per Hour') }}
                                        @endif
                                            </div>
                                        <span>
                                            {{ $server->product->price == round($server->product->price) ? round($server->product->price) : $server->product->price }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-center">
                            <a href="{{ config('SETTINGS::SYSTEM:PTERODACTYL:URL') }}/server/{{ $server->identifier }}"
                                target="__blank"
                                class="btn btn-info text-center float-left ml-2"
                                data-toggle="tooltip" data-placement="bottom" title="{{ __('Manage Server') }}">
                                <i class="fas fa-tools mx-2"></i>
                            </a>
                            @if(config("SETTINGS::SYSTEM:PTERODACTYL:ADMIN_USER_TOKEN"))
                            <a href="{{ route('servers.show', ['server' => $server->id])}}"
                            	class="btn btn-info text-center mr-3"
                            	data-toggle="tooltip" data-placement="bottom" title="{{ __('Server Settings') }}">
                                <i class="fas fa-cog mx-2"></i>
                            </a>
                            @endif
                            <button onclick="handleServerCancel('{{ $server->id }}');" target="__blank"
                                class="btn btn-warning  text-center"
                                {{ $server->suspended || $server->cancelled ? "disabled" : "" }}
                                data-toggle="tooltip" data-placement="bottom" title="{{ __('Cancel Server') }}">
                                <i class="fas fa-ban mx-2"></i>
                            </button>
                            <button onclick="handleServerDelete('{{ $server->id }}');" target="__blank"
                                class="btn btn-danger  text-center float-right mr-2"
                                data-toggle="tooltip" data-placement="bottom" title="{{ __('Delete Server') }}">
                                <i class="fas fa-trash mx-2"></i>
                            </button>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
            <!-- END CUSTOM CONTENT -->
        </div>
    </section>
    <!-- END CONTENT -->

    <script>
        const handleServerCancel = (serverId) => {
            // Handle server cancel with sweetalert
            Swal.fire({
                title: "{{ __('Cancel Server?') }}",
                text: "{{ __('This will cancel your current server to the next billing period. It will get suspended when the current period runs out.') }}",
                icon: 'warning',
                confirmButtonColor: '#d9534f',
                showCancelButton: true,
                confirmButtonText: "{{ __('Yes, cancel it!') }}",
                cancelButtonText: "{{ __('No, abort!') }}",
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    // Delete server
                    fetch("{{ route('servers.cancel', '') }}" + '/' + serverId, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(() => {
                        window.location.reload();
                    });
                    return
                }
            })
        }

        const handleServerDelete = (serverId) => {
            Swal.fire({
                title: "{{ __('Delete Server?') }}",
                html: "{{!! __('This is an irreversible action, all files of this server will be removed. <strong>No funds will get refunded</strong>. We recommend deleting the server when server is suspended.') !!}}",
                icon: 'warning',
                confirmButtonColor: '#d9534f',
                showCancelButton: true,
                confirmButtonText: "{{ __('Yes, delete it!') }}",
                cancelButtonText: "{{ __('No, abort!') }}",
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    // Delete server
                    fetch("{{ route('servers.destroy', '') }}" + '/' + serverId, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(() => {
                        window.location.reload();
                    });
                    return
                }
            });

        }

        document.addEventListener('DOMContentLoaded', () => {
            $('[data-toggle="popover"]').popover();
        });

        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })
    </script>
@endsection
