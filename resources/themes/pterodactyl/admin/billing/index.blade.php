@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'basic'])

@section('title')
    Billing
@endsection

@section('content-header')
    <h1>Billing<small>Monitor your income.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Billing</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12 col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                By Country
            </div>
            <div class="box-body">
                <canvas id="country_chart" width="100%" height="50"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                By Month
            </div>
            <div class="box-body">
                <canvas id="month_chart" width="100%" height="50"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-md-4">
        <div class="info-box bg-blue">
            <span class="info-box-icon"><i class="fa fa-globe"></i></span>
            <div class="info-box-content number-info-box-content">
                <span class="info-box-text">{{ date('Y')}} Income </span>
                <span class="info-box-number">${{ number_format($this_year_income, 2) }}</span>
            </div>
        </div>
        <div class="info-box bg-blue">
            <span class="info-box-icon"><i class="ion ion-ios-calendar"></i></span>
            <div class="info-box-content number-info-box-content">
                <span class="info-box-text">{{ date('F') }} Income</span>
                <span class="info-box-number">${{ number_format($this_month_income, 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Invoices History</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.billing.new') }}" class="btn btn-sm btn-primary">Create New</a>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-hover">
                    <tr>
                        <th>#</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>User</th>
                        <th></th>
                    </tr>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td><b>#{{ $invoice->id }}</b></td>
                            <td>$ {{ number_format($invoice->amount, 2) }}</td>
                            <td>{{ date(__('strings.date_format'), strtotime($invoice->created_at)) }}</td>
                            <td>{{ $invoice->user->getNameAttribute() }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.billing.pdf', ['id' => $invoice->id]) }}"><i class="fa fa-file-pdf-o"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </table>
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        var income_month_graph = JSON.parse('{!! json_encode($income_month_graph) !!}');
        var income_country_graph = JSON.parse('{!! json_encode($income_country_graph) !!}');
    </script>
    {!! Theme::js('vendor/chartjs/chart.min.js') !!}
    {!! Theme::js('js/admin/billing.js') !!}
@endsection