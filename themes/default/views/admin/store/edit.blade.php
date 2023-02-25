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
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.store.index') }}">{{ __('Store') }}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                href="{{ route('admin.store.edit', $shopProduct->id) }}">{{ __('Edit') }}</a>
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
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('admin.store.update', $shopProduct->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="d-flex flex-row-reverse">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" @if ($shopProduct->disabled) checked @endif name="disabled"
                                            class="custom-control-input custom-control-input-danger" id="switch1">
                                        <label class="custom-control-label" for="switch1">{{ __('Disabled') }} <i
                                                data-toggle="popover" data-trigger="hover"
                                                data-content="{{ __('Will hide this option from being selected') }}"
                                                class="fas fa-info-circle"></i></label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="type">{{ __('Type') }}</label>
                                    <select required name="type" id="type"
                                        class="custom-select  @error('name') is-invalid @enderror">
                                        <option @if ($shopProduct->type == 'credits') selected @endif value="Credits">{{ CREDITS_DISPLAY_NAME }}</option>
                                        <option @if ($shopProduct->type == 'Server slots') selected @endif value="Server slots">{{__("Server Slots")}}</option>
                                    </select>
                                    @error('name')
                                        <div class="text-danger">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="currency_code">{{ __('Currency code') }}</label>
                                    <select required name="currency_code" id="currency_code"
                                        class="custom-select  @error('name') is-invalid @enderror">
                                        @foreach ($currencyCodes as $code)
                                            <option @if ($shopProduct->currency_code == $code) selected @endif value="{{ $code }}">
                                                {{ $code }}</option>
                                        @endforeach
                                    </select>
                                    @error('currency_code')
                                        <div class="text-danger">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <div class="text-muted">
                                        {{ __('Checkout the paypal docs to select the appropriate code') }} <a
                                            target="_blank"
                                            href="https://developer.paypal.com/docs/api/reference/currency-codes/">link</a>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="price">Price</label>
                                    <input value="{{ $shopProduct->price }}" id="price" name="price" type="number"
                                        placeholder="10.00" step="any"
                                        class="form-control @error('price') is-invalid @enderror" required="required">
                                    @error('price')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="quantity">Quantity</label>
                                    <input value="{{ $shopProduct->quantity }}" id="quantity" name="quantity"
                                        type="number" placeholder="1000"
                                        class="form-control @error('quantity') is-invalid @enderror" required="required">
                                    @error('quantity')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <div class="text-muted">
                                        {{ __('Amount given to the user after purchasing') }}
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="display">Display</label>
                                    <input value="{{ $shopProduct->display }}" id="display" name="display" type="text"
                                        placeholder="750 + 250" class="form-control @error('display') is-invalid @enderror"
                                        required="required">
                                    @error('display')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <div class="text-muted">
                                        {{ __('This is what the user sees at store and checkout') }}
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <input value="{{ $shopProduct->description }}" id="description" name="description"
                                        type="text" placeholder="{{ __('Adds 1000 credits to your account') }}"
                                        class="form-control @error('description') is-invalid @enderror" required="required">
                                    @error('description')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <div class="text-muted">
                                        {{ __('This is what the user sees at checkout') }}
                                    </div>
                                </div>


                                <div class="form-group text-right">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Submit') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
    <!-- END CONTENT -->



@endsection
