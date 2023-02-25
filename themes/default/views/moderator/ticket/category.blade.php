@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Ticket Categories') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                                       href="{{ route("moderator.ticket.category.index") }}">{{ __('Ticket Categories') }}</a>
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
                                <h5 class="card-title"><i class="fas fas fa-users mr-2"></i>{{__('Categories')}}</h5>
                            </div>
                        </div>
                        <div class="card-body table-responsive">

                            <table id="datatable" class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{{__('ID')}}</th>
                                    <th>{{__('Name')}}</th>
                                    <th>{{__('Tickets')}}</th>
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
                            <h5 class="card-title">{{__('Add Category')}}
                        </div>
                        <div class="card-body">
                            <form action="{{route("moderator.ticket.category.store")}}" method="POST" class="ticket-form">
                            @csrf
                                <div class="form-group ">
                                    <label for="name" class="control-label">{{__("Name")}}</label>
                                    <input id="name" type="text" class="form-control" name="name" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    {{__('Submit')}}
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">{{__('Edit Category')}}
                        </div>
                        <div class="card-body">
                            <form action="{{route("moderator.ticket.category.update","1")}}" method="POST" class="ticket-form">
                                @csrf
                                @method('PATCH')
                                <select id="category" style="width:100%" class="custom-select" name="category"
                                        required autocomplete="off" @error('category') is-invalid @enderror>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ __($category->name) }}</option>
                                    @endforeach
                                </select>

                                <div class="form-group ">
                                    <label for="name" class="control-label">{{__("New Name")}}</label>
                                    <input id="name" type="text" class="form-control" name="name" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
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
                ajax: "{{route('moderator.ticket.category.datatable')}}",
                columns: [
                    {data: 'id'},
                    {data: 'name'},
                    {data: 'tickets'},
                    {data: 'created_at', sortable: false},
                    {data: 'actions', sortable: false},
                ],
                fnDrawCallback: function( oSettings ) {
                    $('[data-toggle="popover"]').popover();
                }
            });
        });

            document.addEventListener('DOMContentLoaded', (event) => {
            $('.custom-select').select2();
        })

    </script>
@endsection

