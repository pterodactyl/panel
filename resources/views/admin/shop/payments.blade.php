@extends('layouts.admin')

@section('title')
    Payments
@endsection

@section('content-header')
    <h1>Payments <small>View payments and invoices.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Payments</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12 col-md-3">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fa fa-money"></i></span>
                <div class="info-box-content number-info-box-content">
                    <span class="info-box-text">Total Income</span>
                    <span class="info-box-number">{{ $total }} {{ $currency }}</span>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-3">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fa fa-calendar"></i></span>
                <div class="info-box-content number-info-box-content">
                    <span class="info-box-text">Current Month Income</span>
                    <span class="info-box-number">{{ $currentMonth }} {{ $currency }}</span>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-3">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fa fa-gamepad"></i></span>
                <div class="info-box-content number-info-box-content">
                    <span class="info-box-text">Previous Month Income</span>
                    <span class="info-box-number">{{ $prevMonth }} {{ $currency }}</span>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-3">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fa fa-file"></i></span>
                <div class="info-box-content number-info-box-content">
                    <span class="info-box-text">Payment Count</span>
                    <span class="info-box-number">{{ $count }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Payments</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>#</th>
                                <th>Amount</th>
                                <th>Client</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td>{{ $payment->id }}</td>
                                    <td><span class="label label-info">{{ $payment->amount }} {{ $currency }}</span></td>
                                    <td><a href="{{ route('admin.users.view', $payment->user_id) }}" target="_blank">{{ $payment->name_first }} {{ $payment->name_last }}</a></td>
                                    <td><span class="label label-{{ $payment->payment_type == 'paypal' ? 'primary' : 'success' }}">{{ ucfirst($payment->payment_type) }}</span></td>
                                    <td><code>{{ $payment->created_at }}</code></td>
                                    <td><a class="btn btn-xs btn-primary" target="_blank" href="{{ route('admin.shop.payments.invoice', $payment->id) }}">View Invoice</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @if($payments->hasPages())
            <div class="col-md-12 text-center">{!! $payments->render() !!}</div>
        @endif
    </div>
@endsection
