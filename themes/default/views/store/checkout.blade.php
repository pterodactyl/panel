@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Store') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a class="" href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                href="{{ route('store.index') }}">{{ __('Store') }}</a>
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
                <div class="col-12">

                    <form x-data="{ payment_method: '', clicked: false }" action="{{ route('payment.pay') }}" method="POST">
                        @csrf
                        @method('post')
                        <!-- Main content -->
                        <div class="invoice p-3 mb-3">
                            <!-- title row -->
                            <div class="row">
                                <div class="col-12">
                                    <h4>
                                        <i class="fas fa-globe"></i> {{ config('app.name', 'Laravel') }}
                                        <small class="float-right">{{ __('Date') }}:
                                            {{ Carbon\Carbon::now()->isoFormat('LL') }}</small>
                                    </h4>
                                </div>
                                <!-- /.col -->
                            </div>

                            <!-- Table row -->
                            <div class="row">
                                <div class="col-12 table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Quantity') }}</th>
                                                <th>{{ __('Product') }}</th>
                                                <th>{{ __('Description') }}</th>
                                                <th>{{ __('Subtotal') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>1</td>
                                                <td><i class="fa fa-coins mr-2"></i>{{ $product->quantity }}
                                                    {{ strtolower($product->type) == 'credits' ? CREDITS_DISPLAY_NAME : $product->type }}
                                                </td>
                                                <td>{{ $product->description }}</td>
                                                <td>{{ $product->formatToCurrency($product->price) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                </div>
                                <!-- /.col -->
                            </div>
                            <!-- /.row -->

                            <div class="row">
                                <!-- accepted payments column -->
                                <div class="col-6">
                                    @if (!$productIsFree)
                                        <p class="lead">{{ __('Payment Methods') }}:</p>

                                        <div class="d-flex flex-wrap  flex-direction-row">

                                            @foreach ($paymentGateways as $gateway)
                                                <div class="ml-2">
                                                    <label class="text-center" for="{{ $gateway->name }}">
                                                        <img class="mb-3" height="50"
                                                            src="{{ $gateway->image }}"></br>
                                                        <input x-on:click="console.log(payment_method)"
                                                            x-model="payment_method" type="radio"
                                                            id="{{ $gateway->name }}" value="{{ $gateway->name }}">
                                                        </input>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                </div>
                                <!-- /.col -->
                                <div class="col-6">
                                    <p class="lead">{{ __('Amount Due') }}
                                        {{ Carbon\Carbon::now()->isoFormat('LL') }}</p>

                                    <div class="table-responsive">
                                        <table class="table">
                                            @if ($discountpercent && $discountvalue)
                                                <tr>
                                                    <th>{{ __('Discount') }} ({{ $discountpercent }}%):</th>
                                                    <td>{{ $product->formatToCurrency($discountvalue) }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <th style="width:50%">{{ __('Subtotal') }}:</th>
                                                <td>{{ $product->formatToCurrency($discountedprice) }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Tax') }} ({{ $taxpercent }}%):</th>
                                                <td>{{ $product->formatToCurrency($taxvalue) }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Total') }}:</th>
                                                <td>{{ $product->formatToCurrency($total) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <!-- /.col -->
                            </div>
                            <!-- /.row -->

                            <!-- this row will not appear when printing -->
                            <div class="row no-print">
                                <div class="col-12">
                                    <button :disabled="(!payment_method || clicked) && {{ !$productIsFree }}"
                                        :class="(!payment_method || clicked) && {{ !$productIsFree }} ? 'disabled' : ''"
                                        class="btn btn-success float-right"><i class="far fa-credit-card mr-2"
                                            @click="clicked = true"></i>
                                        @if ($productIsFree)
                                            {{ __('Get for free') }}
                                        @else
                                            {{ __('Submit Payment') }}
                                        @endif
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <!-- /.invoice -->
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div>
    </section>
    <!-- END CONTENT -->
@endsection
