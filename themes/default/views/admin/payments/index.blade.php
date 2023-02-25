@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{__('Payments')}}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="text-muted" href="{{route('admin.payments.index')}}">{{__('Payments')}}</a>
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

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>{{ __('Payments') }}</h5>
                    <div class="float-right">
                        <a href="{{ route('admin.invoices.downloadAllInvoices') }}"><button
                                class="btn btn-info">{{ __('Download all Invoices') }}</button></a>
                    </div>
                </div>

                <div class="card-body table-responsive">
                    <table id="datatable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Product Price') }}</th>
                                <th>{{ __('Tax Value') }}</th>
                                <th>{{ __('Tax Percentage') }}</th>
                                <th>{{ __('Total Price') }}</th>
                                <th>{{ __('Payment ID') }}</th>
                                <th>{{ __('Payment Method') }}</th>
                                <th>{{ __('Created at') }}</th>
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
        </div>
    </section>
    <!-- END CONTENT -->

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            $('#datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.3/i18n/{{config("SETTINGS::LOCALE:DATATABLES")}}.json'
                },
                processing: true,
                serverSide: true,
                stateSave: true,
                ajax: "{{ route('admin.payments.datatable') }}",
                order: [[ 10, "desc" ]],
                columns: [
                    {data: 'id',name: 'payments.id'},
                    {data: 'type'},
                    {data: 'user'},
                    {data: 'amount'},
                    {data: 'price'},
                    {data: 'tax_value'},
                    {data: 'tax_percent'},
                    {data: 'total_price'},
                    {data: 'payment_id'},
                    {data: 'payment_method'},
                    {data: 'created_at', type: 'num', render: {_: 'display', sort: 'raw'}},
                    {data: 'actions' , sortable : false},
                ],
                fnDrawCallback: function(oSettings) {
                    $('[data-toggle="popover"]').popover();
                },
            });
        });
    </script>

@endsection
