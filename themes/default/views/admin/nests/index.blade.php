<!--
THIS FILE IS DEPRECATED
 -->


@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{__('Nests')}}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__('Dashboard')}}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                                       href="{{route('admin.nests.index')}}">{{__('Nests')}}</a></li>
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
                        <h5 class="card-title"><i class="fas fa-sitemap mr-2"></i>{{__('Nests')}}</h5>
                        <a href="{{route('admin.nests.sync')}}" class="btn btn-sm btn-info"><i
                                class="fas fa-sync mr-1"></i>{{__('Sync')}}</a>
                    </div>
                </div>

                <div class="card-body table-responsive">

                    <table id="datatable" class="table table-striped">
                        <thead>
                        <tr>
                            <th>{{__('Active')}}</th>
                            <th>{{__('ID')}}</th>
                            <th>{{__('eggs')}}</th>
                            <th>{{__('Name')}}</th>
                            <th>{{__('Description')}}</th>
                            <th>{{__('Created at')}}</th>

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
            return confirm({{__("Are you sure you wish to delete?")}}) !== false;
        }

        document.addEventListener("DOMContentLoaded", function () {
            $('#datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.3/i18n/{{config("SETTINGS::LOCALE:DATATABLES")}}.json'
                },
                processing: true,
                serverSide: true,
                stateSave: true,
                ajax: "{{route('admin.nests.datatable')}}",
                order: [[ 1, "desc" ]],
                columns: [
                    {data: 'actions', name : 'disabled'},
                    {data: 'id'},
                    {data: 'eggs' , sortable : false},
                    {data: 'name' , name : 'nests.name'},
                    {data: 'description'},
                    {data: 'created_at'},

                ]
            });
        });
    </script>



@endsection
