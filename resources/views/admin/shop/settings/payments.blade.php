@extends('layouts.admin')

@section('title')
    Settings | Payments
@endsection

@section('content-header')
    <h1>Payment Settings <small>Setup your payment providers.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Payments</li>
    </ol>
@endsection

@section('content')
    @include('admin.shop.settings.partials.navigation')

    <div class="row">
        <div class="col-xs-12 col-lg-offset-1">
            <div class="row">
                <div class="col-xs-12 col-lg-4">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">PayPal Settings</h3>
                        </div>
                        <form method="post" action="{{ route('admin.shop.settings.payments') }}">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="paypal_enabled">Enabled</label>
                                    <select id="paypal_enabled" name="paypal_enabled" class="form-control">
                                        <option value="0" {{ old('paypal_enabled', $paypal['enabled']) == 0 ? 'selected' : '' }}>Disabled</option>
                                        <option value="1" {{ old('paypal_enabled', $paypal['enabled']) == 1 ? 'selected' : '' }}>Enabled</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="paypal_mode">Mode</label>
                                    <select id="paypal_mode" name="paypal_mode" class="form-control">
                                        <option value="sandbox" {{ old('paypal_mode', $paypal['mode']) == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                        <option value="live" {{ old('paypal_mode', $paypal['mode']) == 'live' ? 'selected' : '' }}>Live</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="paypal_key">Key</label>
                                    <input type="text" class="form-control" id="paypal_key" name="paypal_key" placeholder="PayPal Rest Api Key" value="{{ old('paypal_key', $paypal['key']) }}">
                                </div>
                                <div class="form-group">
                                    <label for="paypal_secret">Secret</label>
                                    <input type="text" class="form-control" id="paypal_secret" name="paypal_secret" placeholder="PayPal Rest Api Secret" value="{{ old('paypal_secret', $paypal['secret']) }}">
                                </div>
                            </div>
                            <div class="box-footer">
                                {!! csrf_field() !!}
                                <input type="hidden" name="type" value="paypal">
                                <button class="btn btn-success pull-right">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-xs-12 col-lg-2">
                    <div class="box box-success">
                        <div class="box-header with-border">
                            <h3 class="box-title">Global Settings</h3>
                        </div>
                        <form method="post" action="{{ route('admin.shop.settings') }}">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="currency">Currency</label>
                                    <select name="currency" id="currency" class="form-control">
                                        @foreach ($currencies as $curr => $item)
                                            <option value="{{ $curr }}" {{ old('currency', $currency) == $curr ? 'selected' : '' }}>{{ $curr }} - {{ $item }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="min_amount">Minimum Deposit Amount</label>
                                    <input type="number" id="min_amount" name="min_amount" value="{{ old('min_amount', $min_amount) }}" class="form-control" step="0.01">
                                </div>
                                <div class="form-group">
                                    <label for="max_amount">Maximum Deposit Amount</label>
                                    <input type="number" id="max_amount" name="max_amount" value="{{ old('max_amount', $max_amount) }}" class="form-control" step="0.01">
                                </div>
                            </div>
                            <div class="box-footer">
                                {!! csrf_field() !!}
                                <button class="btn btn-success pull-right">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-xs-12 col-lg-4">
                    <div class="box box-warning">
                        <div class="box-header with-border">
                            <h3 class="box-title">Stripe Settings</h3>
                        </div>
                        <form method="post" action="{{ route('admin.shop.settings.payments') }}">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="stripe_enabled">Enabled</label>
                                    <select id="stripe_enabled" name="stripe_enabled" class="form-control">
                                        <option value="0" {{ old('stripe_enabled', $stripe['enabled']) == 0 ? 'selected' : '' }}>Disabled</option>
                                        <option value="1" {{ old('stripe_enabled', $stripe['enabled']) == 1 ? 'selected' : '' }}>Enabled</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="stripe_mode">Mode</label>
                                    <select id="stripe_mode" name="stripe_mode" class="form-control">
                                        <option value="sandbox" {{ old('stripe_mode', $stripe['mode']) == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                        <option value="live" {{ old('stripe_mode', $stripe['mode']) == 'live' ? 'selected' : '' }}>Live</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="stripe_key">Key</label>
                                    <input type="text" class="form-control" id="stripe_key" name="stripe_key" placeholder="Stripe Rest Api Key" value="{{ old('stripe_key', $stripe['key']) }}">
                                </div>
                                <div class="form-group">
                                    <label for="stripe_secret">Secret</label>
                                    <input type="text" class="form-control" id="stripe_secret" name="stripe_secret" placeholder="Stripe Rest Api Secret" value="{{ old('stripe_secret', $stripe['secret']) }}">
                                </div>
                            </div>
                            <div class="box-footer">
                                {!! csrf_field() !!}
                                <input type="hidden" name="type" value="stripe">
                                <button class="btn btn-success pull-right">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
