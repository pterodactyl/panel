@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Ticket Blacklist') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                                       href="{{ route('moderator.ticket.blacklist') }}">{{ __('Ticket Blacklist') }}</a>
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
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title"><i class="fas fas fa-users mr-2"></i>{{__('Blacklist List')}}</h5>
                            </div>
                        </div>
                        <div class="card-body table-responsive">

                            <table id="datatable" class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{{__('User')}}</th>
                                    <th>{{__('Status')}}</th>
                                    <th>{{__('Reason')}}</th>
                                    <th>{{__('Created At')}}</th>
                                    <th>{{__('Actions')}}</th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">{{__('Add To Blacklist')}}
                                <i data-toggle="popover"
                                data-trigger="hover"
                                data-content="{{__('please make the best of it')}}"
                                class="fas fa-info-circle"></i></h5>
                        </div>
                        <div class="card-body">
                            <form action="{{route('moderator.ticket.blacklist.add')}}" method="POST" class="ticket-form">
                            @csrf
                                <div class="custom-control mb-3 p-0">
                                    <label for="user_id">{{ __('User') }}:
                                        <i data-toggle="popover" data-trigger="hover"
                                        data-content="{{ __('Please note, the blacklist will make the user unable to make a ticket/reply again') }}" class="fas fa-info-circle"></i>
                                    </label>
                                    <select id="user_id" style="width:100%" class="custom-select" name="user_id" required
                                            autocomplete="off" @error('user_id') is-invalid @enderror>
                                    </select>
                                </div>
                                <div class="form-group ">
                                    <label for="reason" class="control-label">{{__("Reason")}}</label>
                                    <input id="reason" type="text" class="form-control" name="reason" placeholder="Input Some Reason" required>
                                </div>
                                <button type="submit" class="btn btn-primary ticket-once">
                                    {{__('Submit')}}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            $('#datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.3/i18n/{{config("SETTINGS::LOCALE:DATATABLES")}}.json'
                },
                processing: true,
                serverSide: true,
                stateSave: true,
                ajax: "{{route('moderator.ticket.blacklist.datatable')}}",
                columns: [
                    {data: 'user' , name : 'user.name'},
                    {data: 'status'},
                    {data: 'reason'},
                    {data: 'created_at', sortable: false},
                    {data: 'actions', sortable: false},
                ],
                fnDrawCallback: function( oSettings ) {
                    $('[data-toggle="popover"]').popover();
                }
            });
        });
    </script>
    <script type="application/javascript">
        function initUserIdSelect(data) {
            function escapeHtml(str) {
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }

            $('#user_id').select2({
                ajax: {
                    url: '/admin/users.json',
                    dataType: 'json',
                    delay: 250,

                    data: function (params) {
                        return {
                            filter: { email: params.term },
                            page: params.page,
                        };
                    },

                    processResults: function (data, params) {
                        return { results: data };
                    },

                    cache: true,
                },

                data: data,
                escapeMarkup: function (markup) { return markup; },
                minimumInputLength: 2,
                templateResult: function (data) {
                    if (data.loading) return escapeHtml(data.text);

                    return '<div class="user-block"> \
                        <img class="img-circle img-bordered-xs" src="' + escapeHtml(data.avatarUrl) + '?s=120" alt="User Image"> \
                        <span class="username"> \
                            <a href="#">' + escapeHtml(data.name) +'</a> \
                        </span> \
                        <span class="description"><strong>' + escapeHtml(data.email) + '</strong>' + '</span> \
                    </div>';
                },
                templateSelection: function (data) {
                    return '<div> \
                        <span> \
                            <img class="img-rounded img-bordered-xs" src="' + escapeHtml(data.avatarUrl) + '?s=120" style="height:28px;margin-top:-4px;" alt="User Image"> \
                        </span> \
                        <span style="padding-left:5px;"> \
                            ' + escapeHtml(data.name) + ' (<strong>' + escapeHtml(data.email) + '</strong>) \
                        </span> \
                    </div>';
                }

            });
        }

        $(document).ready(function() {
            @if (old('user_id'))
                $.ajax({
                    url: '/admin/users.json?user_id={{ old('user_id') }}',
                    dataType: 'json',
                }).then(function (data) {
                    initUserIdSelect([ data ]);
                });
            @else
                initUserIdSelect();
            @endif
        });
    </script>
@endsection

