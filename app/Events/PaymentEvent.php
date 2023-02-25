<?php

namespace App\Events;

use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Payment $payment;
    public ShopProduct $shopProduct;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Payment $payment, ShopProduct $shopProduct)
    {
        $this->user = $user;
        $this->payment = $payment;
        $this->shopProduct = $shopProduct;
    }
}
