@extends('layouts.admin')

@section('title')
    Settings | Invoice
@endsection

@section('content-header')
    <h1>Invoice Settings <small>Set your invoice settings.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Invoice</li>
    </ol>
@endsection

@section('content')
    @include('admin.shop.settings.partials.navigation')

    <div class="row">
        <div class="col-xs-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Invoice Settings</h3>
                </div>
                <form method="post" action="{{ route('admin.shop.settings.invoice') }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="enabled">Enabled</label>
                            <select id="enabled" name="enabled" class="form-control">
                                <option value="0" {{ old('enabled', $enabled) == 0 ? 'selected' : '' }}>Disabled</option>
                                <option value="1" {{ old('enabled', $enabled) == 1 ? 'selected' : '' }}>Enabled</option>
                            </select>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $name) }}">
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-12 col-lg-6">
                                <label for="address">Address</label>
                                <input type="text" class="form-control" name="address" id="address" value="{{ old('address', $address) }}">
                            </div>
                            <div class="form-group col-xs-12 col-lg-6">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $phone) }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-12 col-lg-6">
                                <label for="code">Code</label>
                                <input type="text" class="form-control" name="code" id="code" value="{{ old('code', $code) }}">
                            </div>
                            <div class="form-group col-xs-12 col-lg-6">
                                <label for="vat">Vat Code</label>
                                <input type="text" class="form-control" name="vat" id="vat" value="{{ old('vat', $vat) }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="tax">Tax Rate</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="tax" id="tax" value="{{ old('tax', $tax) }}">
                                <span class="input-group-addon">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection