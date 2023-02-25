<?php

namespace App\Traits;

use App\Models\PartnerDiscount;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Notifications\InvoiceNotification;
use Illuminate\Support\Facades\Storage;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Classes\Party;
use LaravelDaily\Invoices\Invoice;
use Symfony\Component\Intl\Currencies;

trait Invoiceable
{
    public function createInvoice(Payment $payment, ShopProduct $shopProduct)
    {
        $user = $payment->user;
        //create invoice
        $lastInvoiceID = \App\Models\Invoice::where("invoice_name", "like", "%" . now()->format('mY') . "%")->count("id");
        $newInvoiceID = $lastInvoiceID + 1;
        $logoPath = storage_path('app/public/logo.png');

        $seller = new Party([
            'name' => config("SETTINGS::INVOICE:COMPANY_NAME"),
            'phone' => config("SETTINGS::INVOICE:COMPANY_PHONE"),
            'address' => config("SETTINGS::INVOICE:COMPANY_ADDRESS"),
            'vat' => config("SETTINGS::INVOICE:COMPANY_VAT"),
            'custom_fields' => [
                'E-Mail' => config("SETTINGS::INVOICE:COMPANY_MAIL"),
                "Web" => config("SETTINGS::INVOICE:COMPANY_WEBSITE")
            ],
        ]);

        $customer = new Buyer([
            'name' => $user->name,
            'custom_fields' => [
                'E-Mail' => $user->email,
                'Client ID' => $user->id,
            ],
        ]);
        $item = (new InvoiceItem())
            ->title($shopProduct->description)
            ->pricePerUnit($shopProduct->price);

        $notes = [
            __("Payment method") . ": " . $payment->payment_method,
        ];
        $notes = implode("<br>", $notes);


        $invoice = Invoice::make()
            ->template('controlpanel')
            ->name(__("Invoice"))
            ->buyer($customer)
            ->seller($seller)
            ->discountByPercent(PartnerDiscount::getDiscount())
            ->taxRate(floatval($shopProduct->getTaxPercent()))
            ->shipping(0)
            ->addItem($item)
            ->status(__($payment->status))
            ->series(now()->format('mY'))
            ->delimiter("-")
            ->sequence($newInvoiceID)
            ->serialNumberFormat(config("SETTINGS::INVOICE:PREFIX") . '{DELIMITER}{SERIES}{SEQUENCE}')
            ->currencyCode(strtoupper($payment->currency_code))
            ->currencySymbol(Currencies::getSymbol(strtoupper($payment->currency_code)))
            ->notes($notes);

        if (file_exists($logoPath)) {
            $invoice->logo($logoPath);
        }

        //Save the invoice in "storage\app\invoice\USER_ID\YEAR"
        $invoice->filename = $invoice->getSerialNumber() . '.pdf';
        $invoice->render();
        Storage::disk("local")->put("invoice/" . $user->id . "/" . now()->format('Y') . "/" . $invoice->filename, $invoice->output);

        \App\Models\Invoice::create([
            'invoice_user' => $user->id,
            'invoice_name' => $invoice->getSerialNumber(),
            'payment_id' => $payment->payment_id,
        ]);

        //Send Invoice per Mail
        $user->notify(new InvoiceNotification($invoice, $user, $payment));
    }
}
