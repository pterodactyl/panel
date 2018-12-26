@extends('layouts.master')

@section('title')
    @lang('base.billing.header')
@endsection

@section('content-header')
    <h1>@lang('base.billing.header')<small>@lang('base.billing.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li class="active">@lang('strings.billing')</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('base.billing.summary.header')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-6 text-center">
                            <b>@lang('base.billing.summary.this_month_charges')</b>
                            <h1>$ {{ number_format(Auth::user()->monthly_cost, 2) }}</h1>                            
                        </div>
                        <div class="col-xs-6 text-center {{ Auth::user()->balance > 0 ? 'text-success' : 'text-danger' }}">
                            <b>@lang('base.billing.summary.account_balance')</b>
                            <h1>$ {{ number_format(Auth::user()->balance, 2) }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            @if ($user->stripe_customer_id)
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('base.billing.unlink.heading')</h3>
                </div>
                <form method="POST" action="{{ route('account.billing.unlink') }}">
                    <div class="box-body">
                        <p>@lang('base.billing.unlink.description', ['brand' => $user->stripe_card_brand, 'last4' => $user->stripe_card_last4])</p>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button type="submit" class="btn btn-danger btn-sm">@lang('base.billing.unlink.submit_button')</button>
                    </div>
                </form>
            </div>
            @else
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('base.billing.link.heading')</h3>
                </div>
                <form method="POST" action="{{ route('account.billing.link') }}">
                    <div class="box-body">
                        <p>@lang('base.billing.link.description')</p>
                        <div class="form-group">
                            <label class="control-label">@lang('base.billing.link.credit_card_info')</label>
                            <div>
                                <div id="card-element"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">@lang('base.billing.link.amount')</label>
                            <div>
                                <input type="number" name="amount" value="20" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <input type="hidden" name="card_token">
                        <input type="hidden" name="card_brand">
                        <input type="hidden" name="card_last4">
                        {!! csrf_field() !!}
                        <button type="submit" class="btn btn-success btn-sm">@lang('base.billing.link.submit_button')</button>
                    </div>
                </form>
            </div>
            @endif
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('base.billing.charge.heading')</h3>
                </div>
                <form method="POST" action="{{ route('account.billing.paypal') }}">
                    <div class="box-body">
                        <p>@lang('base.billing.charge.description')</p>
                        <div class="form-group">
                            <label class="control-label">@lang('base.billing.charge.amount')</label>
                            <div>
                                <input type="number" name="amount" value="20" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button type="submit" class="btn btn-success btn-sm">@lang('base.billing.charge.submit_button')</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-xs-6">
            <div class="box">
                <form action="{{ route('account.billing.info') }}" method="POST">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('base.billing.info.header')</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-sm-6">
                                <label for="first_name" class="control-label">@lang('base.billing.info.first_name')</label>
                                <div>
                                    <input type="text" class="form-control" name="first_name" value="{{ old('first_name', $user->billing_first_name) }}" />
                                </div>
                            </div>
                            <div class="form-group col-sm-6">
                                <label for="last_name" class="control-label">@lang('base.billing.info.last_name')</label>
                                <div>
                                    <input type="text" class="form-control" name="last_name" value="{{ old('last_name', $user->billing_last_name) }}" />
                                </div>
                            </div>
                            <div class="form-group col-sm-12">
                                <label for="address" class="control-label">@lang('base.billing.info.address')</label>
                                <div>
                                    <input type="text" class="form-control" name="address" value="{{ old('address', $user->billing_address) }}" />
                                </div>
                            </div>
                            <div class="form-group col-sm-4">
                                <label for="city" class="control-label">@lang('base.billing.info.city')</label>
                                <div>
                                    <input type="text" class="form-control" name="city" value="{{ old('city', $user->billing_city) }}" />
                                </div>
                            </div>
                            <div class="form-group col-sm-4">
                                <label for="country" class="control-label">@lang('base.billing.info.country')</label>
                                <div>
                                    <select name="country" class="form-control">
                                        <option selected disabled>--</option>
                                        @foreach ($countries as $code => $name)
                                            <option value="{{ $code }}" {{ old('country', $user->billing_country) == $code ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group col-sm-4">
                                <label for="zip" class="control-label">@lang('base.billing.info.zip')</label>
                                <div>
                                    <input type="text" class="form-control" name="zip" value="{{ old('zip', $user->billing_zip) }}" />
                                </div>
                            </div>
                        </div>
                        <div class="box-footer with-border">
                            {!! csrf_field() !!}
                            <button type="submit" class="btn btn-sm btn-primary">@lang('base.billing.info.submit_button')</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('base.billing.invoices.heading')</h3>
                </div>
                <div class="box-body">
                    <table class="table table-hover">
                        <tr>
                            <th>#</th>
                            <th>@lang('base.billing.invoices.amount')</th>
                            <th>@lang('base.billing.invoices.date')</th>
                            <th></th>
                        </tr>
                        @foreach($invoices as $invoice)
                            <tr>
                                <td><b>#{{ $invoice->id }}</b></td>
                                <td>$ {{ number_format($invoice->amount, 2) }}</td>
                                <td>{{ date(__('strings.date_format'), strtotime($invoice->created_at)) }}</td>
                                <td class="text-right">
                                    <a href="{{ route('account.invoice.pdf', ['id' => $invoice->id]) }}"><i class="fa fa-file-pdf-o"></i></a>
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
    <script src="https://js.stripe.com/v3/"></script>
    <script type="application/javascript">
        var form = $('#card-element').closest('form');
        var stripe = Stripe('{{ env("STRIPE_PUBLIC_KEY") }}');
        var card = stripe.elements().create('card', {
            style: {
                base: {
                    color: '#32325d',
                    lineHeight: '18px',
                    padding: '.5em',
                    margin: {
                        top: '10px',
                    },
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#aab7c4'}
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            }
        });
        card.mount('#card-element');
        form.on('submit', function(ev) {
            var token = form.find('[name="card_token"]');
            if (token.val()) return true;
            ev.preventDefault();
            stripe.createToken(card).then(function(result) {
                if (result.error) return alert(result.error.message);
                form.find('[name="card_brand"]').val(result.token.card.brand);
                form.find('[name="card_last4"]').val(result.token.card.last4);
                token.val(result.token.id);
                form.submit();
            });
            return false;
        })
    </script>
    <style>
        .StripeElement {
            background-color: white;
            height: 40px;
            padding: 10px 12px;
            border-radius: 4px;
            border: 1px solid transparent;
            box-shadow: 0 1px 3px 0 #e6ebf1;
            -webkit-transition: box-shadow 150ms ease;
            transition: box-shadow 150ms ease;}
        .StripeElement--focus {
            box-shadow: 0 1px 3px 0 #cfd7df;}
        .StripeElement--invalid {
            border-color: #fa755a;}
        .StripeElement--webkit-autofill {
            background-color: #fefde5 !important;}
        .AmountSelector {
            display: flex;}
    </style>
@endsection
