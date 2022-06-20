<?php

namespace Pterodactyl\Http\Controllers\Base;

use Exception;
use Stripe\Stripe;
use Stripe\Webhook;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Pterodactyl\Http\Controllers\Controller;
use Stripe\Exception\SignatureVerificationException;

class StripeController extends Controller
{
    public function index(Request $request): Response
    {
        Stripe::setApiKey(config('gateways.stripe.secret'));
        try {
            $event = Webhook::constructEvent($request->getContent(), $request->headers->get('stripe-signature'), config('gateways.stripe.webhook_secret'));
        } catch (SignatureVerificationException $e) {
            return new Response('Error Unvalidated', 401);
        }
        $data = $event->data->toArray()['object'];
        try {
            $msg = match ($event->type) {
                'checkout.session.completed' => $this->completed($data),
                default => 'Ignored - Unhandled Event'
            };
        } catch (Exception $e) {
            $msg = "Failed - {$e}";
        }

        return new Response($msg, 200);
    }

    private function completed(array $data): string
    {
        if ($data['payment_status'] !== 'paid') {
            return 'Failed - Payment Issue';
        }
        $bal = User::query()->select('store_balance')->where('id', '=', $data['metadata']['user_id'])->first()->store_balance;
        User::query()->where('id', '=', $data['metadata']['user_id'])->update(['store_balance' => $bal + $data['metadata']['credit_amount']]);

        return 'Success - Credits Added';
    }
}
