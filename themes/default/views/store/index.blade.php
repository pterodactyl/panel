@extends('layouts.main')
<?php use App\Models\ShopProduct; ?>

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
                        <li class="breadcrumb-item"><a class=""
                                                       href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                                       href="{{ route('store.index') }}">{{ __('Store') }}</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    <!-- MAIN CONTENT -->
    <section class="content">
        <div class="container-fluid">

            <div class="text-right mb-3">
                <button type="button" data-toggle="modal" data-target="#redeemVoucherModal" class="btn btn-primary">
                    <i class="fas fa-money-check-alt mr-2"></i>{{ __('Redeem code') }}
                </button>
            </div>

            @if ($isPaymentSetup && $products->count() > 0)

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fa fa-coins mr-2"></i>{{ CREDITS_DISPLAY_NAME }}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-responsive-sm">
                            <thead>
                            <tr>
                                <th>{{ __('Price') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php /** @var $product ShopProduct */
                            ?>
                            @foreach ($products as $product)
                                <tr>
                                    <td>{{ $product->formatToCurrency($product->price) }}</td>
                                    <td>{{ strtolower($product->type) == 'credits' ? CREDITS_DISPLAY_NAME : $product->type }}
                                    </td>
                                    <td>
                                        @if(strtolower($product->type) == 'credits')
                                            <i class="fa fa-coins mr-2"></i>
                                        @elseif (strtolower($product->type) == 'server slots')
                                            <i class="fa fa-server mr-2"></i>
                                        @endif

                                        {{ $product->display }}</td>
                                    <td><a href="{{ route('checkout', $product->id) }}"
                                           class="btn btn-info">{{ __('Purchase') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            @else
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h4><i class="icon fa fa-ban"></i> @if ($products->count() == 0) {{ __('There are no store products!') }} @else {{ __('The store is not correctly configured!') }} @endif
                    </h4>
                </div>

            @endif


        </div>
    </section>
    <!-- END CONTENT -->

    <script>
        const getUrlParameter = (param) => {
            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            return urlParams.get(param);
        }
        const voucherCode = getUrlParameter('voucher');
        //if voucherCode not empty, open the modal and fill the input
        if (voucherCode) {
            $(function() {
                $('#redeemVoucherModal').modal('show');
                $('#redeemVoucherCode').val(voucherCode);
            });
        }
    </script>


@endsection
