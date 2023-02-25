<div class="tab-pane mt-3" id="payment">
    <form method="POST" enctype="multipart/form-data" class="mb-3"
        action="{{ route('admin.settings.update.paymentsettings') }}">
        @csrf
        @method('PATCH')

        <div class="row">
            {{-- PayPal --}}
            <div class="col-md-3 px-3">
                <div class="row mb-2">
                    <div class="col text-center">
                        <h1>PayPal</h1>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <div class="custom-control p-0">
                        <label for="paypal-client-id">{{ __('PayPal Client-ID') }}:</label>
                        <input x-model="paypal-client-id" id="paypal-client-id" name="paypal-client-id" type="text"
                            value="{{ config('SETTINGS::PAYMENTS:PAYPAL:CLIENT_ID') }}"
                            class="form-control @error('paypal-client-id') is-invalid @enderror">
                    </div>
                </div>

                <div class="form-group mb-3">
                    <div class="custom-control p-0">
                        <label for="paypal-client-secret">{{ __('PayPal Secret-Key') }}:</label>
                        <input x-model="paypal-client-secret" id="paypal-client-secret" name="paypal-client-secret"
                            type="text" value="{{ config('SETTINGS::PAYMENTS:PAYPAL:SECRET') }}"
                            class="form-control @error('paypal-client-secret') is-invalid @enderror">
                    </div>
                </div>


                <div class="form-group mb-3">
                    <div class="custom-control p-0">
                        <label for="paypal-sandbox-id">{{ __('PayPal Sandbox Client-ID') }}:</label>
                        <small class="text-muted">({{ __('optional') }})</small>
                        <input x-model="paypal-sandbox-id" id="paypal-sandbox-id" name="paypal-sandbox-id" type="text"
                            value="{{ config('SETTINGS::PAYMENTS:PAYPAL:SANDBOX_CLIENT_ID') }}"
                            class="form-control @error('paypal-sandbox-id') is-invalid @enderror">
                    </div>
                </div>

                <div class="form-group mb-3">
                    <div class="custom-control p-0">
                        <label for="paypal-sandbox-secret">{{ __('PayPal Sandbox Secret-Key') }}:</label>
                        <small class="text-muted">({{ __('optional') }})</small>
                        <input x-model="paypal-sandbox-secret" id="paypal-sandbox-secret" name="paypal-sandbox-secret"
                            type="text" value="{{ config('SETTINGS::PAYMENTS:PAYPAL:SANDBOX_SECRET') }}"
                            class="form-control @error('paypal-sandbox-secret') is-invalid @enderror">
                    </div>
                </div>
            </div>

            {{-- Stripe --}}
            <div class="col-md-3 px-3">

                <div class="row mb-2">
                    <div class="col text-center">
                        <h1>Stripe</h1>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <div class="custom-control p-0">
                        <label for="stripe-secret">{{ __('Stripe Secret-Key') }}:</label>
                        <input x-model="stripe-secret" id="stripe-secret" name="stripe-secret" type="text"
                            value="{{ config('SETTINGS::PAYMENTS:STRIPE:SECRET') }}"
                            class="form-control @error('stripe-secret') is-invalid @enderror">
                    </div>
                </div>
                <div class="form-group mb-3">
                    <div class="custom-control p-0">
                        <label for="stripe-endpoint-secret">{{ __('Stripe Endpoint-Secret-Key') }}:</label>
                        <input x-model="stripe-endpoint-secret" id="stripe-endpoint-secret"
                            name="stripe-endpoint-secret" type="text"
                            value="{{ config('SETTINGS::PAYMENTS:STRIPE:ENDPOINT_SECRET') }}"
                            class="form-control @error('stripe-endpoint-secret') is-invalid @enderror">
                    </div>
                </div>

                <div class="form-group mb-3">
                    <div class="custom-control p-0">
                        <label for="stripe-test-secret">{{ __('Stripe Test Secret-Key') }}:</label>
                        <small class="text-muted">({{ __('optional') }})</small>
                        <input x-model="stripe-test-secret" id="stripe-test-secret" name="stripe-test-secret"
                            type="text" value="{{ config('SETTINGS::PAYMENTS:STRIPE:TEST_SECRET') }}"
                            class="form-control @error('stripe-test-secret') is-invalid @enderror">
                    </div>
                </div>

                <div class="form-group mb-3">
                    <div class="custom-control p-0">
                        <label for="stripe-endpoint-test-secret">{{ __('Stripe Test Endpoint-Secret-Key') }}:</label>
                        <small class="text-muted">({{ __('optional') }})</small>
                        <input x-model="stripe-endpoint-test-secret" id="stripe-endpoint-test-secret"
                            name="stripe-endpoint-test-secret" type="text"
                            value="{{ config('SETTINGS::PAYMENTS:STRIPE:ENDPOINT_TEST_SECRET') }}"
                            class="form-control @error('stripe-endpoint-test-secret') is-invalid @enderror">
                    </div>
                </div>
                <div class="form-group mb-3">
                    <div class="custom-control p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <label for="stripe-methods">{{ __('Payment Methods') }}:</label>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="Comma separated list of payment methods without whitespaces. <br><br> Example: card,klarna,sepa"
                                class="fas fa-info-circle"></i>
                        </div>
                        <input x-model="stripe-methods" id="stripe-methods" name="stripe-methods" type="text"
                            value="{{ config('SETTINGS::PAYMENTS:STRIPE:METHODS') }}"
                            class="form-control @error('stripe-methods') is-invalid @enderror">
                    </div>
                </div>
            </div>

            {{-- Other --}}
            <div class="col-md-3 px-3">
                <div class="row mb-2">
                    <div class="col text-center">
                        <h1>Other</h1>
                    </div>
                </div>
                <!-- Tax -->
                <div class="form-group mb-3">
                    <div class="custom-control p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <label for="sales-tax">{{ __('Tax Value in %') }}:</label>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="Tax Value that will be added to the total price of the order. <br><br> Example: 19 results in (19%)"
                                class="fas fa-info-circle"></i>
                        </div>
                        <input x-model="sales-tax" id="sales-tax" name="sales-tax" type="number" step="0.01" min="0" max="99999999"
                            value="{{ config('SETTINGS::PAYMENTS:SALES_TAX') }}"
                            class="form-control @error('sales-tax') is-invalid @enderror">
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <button class="btn btn-primary ml-3 mt-3">{{ __('Submit') }}</button>
        </div>
    </form>
</div>
