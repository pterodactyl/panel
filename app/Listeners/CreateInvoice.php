<?php

namespace App\Listeners;

use App\Events\PaymentEvent;
use App\Traits\Invoiceable;

class CreateInvoice
{

    use Invoiceable;

    /**
     * Handle the event.
     *
     * @param  \App\Events\PaymentEvent  $event
     * @return void
     */
    public function handle(PaymentEvent $event)
    {
        if (config('SETTINGS::INVOICE:ENABLED') == 'true') {
            // create invoice using the trait
            $this->createInvoice($event->payment, $event->shopProduct);
        }
    }
}
