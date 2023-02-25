@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Users') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                href="{{ route('admin.users.index') }}">{{ __('Users') }}</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    <!-- MAIN CONTENT -->
    <section class="content">
        <div class="container-fluid">

            <div class="card">

                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title"><i class="fas fa-users mr-2"></i>{{ __('Users') }}</h5>
                        <a href="{{ route('admin.users.notifications') }}" class="btn btn-sm btn-primary"><i
                                class="fas fa-paper-plane mr-1"></i>{{ __('Notify') }}</a>
                    </div>
                </div>

                <div class="card-body table-responsive">

                    <table id="datatable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>discordId</th>
                                <th>ip</th>
                                <th>pterodactyl_id</th>
                                <th>{{ __('Avatar') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ CREDITS_DISPLAY_NAME }}</th>
                                <th>{{ __('Servers') }}</th>
                                <th>{{ __('Referrals') }}</th>
                                <th>{{ __('Verified') }}</th>
                                <th>{{ __('Last seen') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>

                </div>
            </div>


        </div>
        <!-- END CUSTOM CONTENT -->
    </section>
    <!-- END CONTENT -->

    <script>
        function submitResult() {
            return confirm("{{ __('Are you sure you wish to delete?') }}") !== false;
        }

        document.addEventListener("DOMContentLoaded", function() {
            $('#datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.3/i18n/{{ config('SETTINGS::LOCALE:DATATABLES') }}.json'
                },
                processing: true,
                serverSide: true, //why was this set to false before? increased loadingtimes by 10 seconds
                stateSave: true,
                ajax: "{{ route('admin.users.datatable') }}{{ $filter ?? '' }}",
                order: [
                    [11, "desc"]
                ],
                columns: [{
                        data: 'discordId',
                        visible: false,
                        name: 'discordUser.id'
                    },
                    {
                        data: 'pterodactyl_id',
                        visible: false
                    },
                    {
                        data: 'ip',
                        visible: false
                    },
                    {
                        data: 'avatar',
                        sortable: false
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'role'
                    },
                    {
                        data: 'email',
                        name: 'users.email'
                    },
                    {
                        data: 'credits',
                        name: 'users.credits'
                    },
                    {
                        data: 'servers_count'

                    },
                    {
                        data: 'referrals_count',
                    },
                    {
                        data: 'verified',
                        sortable: false
                    },
                    {
                        data: 'last_seen',
                    },
                    {
                        data: 'actions',
                        sortable: false
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    $('[data-toggle="popover"]').popover();
                }
            });
        });
    </script>
@endsection
