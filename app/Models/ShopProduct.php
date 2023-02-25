<?php

namespace App\Models;

use Hidehalo\Nanoid\Client;
use Illuminate\Database\Eloquent\Model;
use NumberFormatter;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ShopProduct extends Model
{
    use LogsActivity;
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            -> logOnlyDirty()
            -> logOnly(['*'])
            -> dontSubmitEmptyLogs();
    }
    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'type',
        'price',
        'description',
        'display',
        'currency_code',
        'quantity',
        'disabled',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (ShopProduct $shopProduct) {
            $client = new Client();

            $shopProduct->{$shopProduct->getKeyName()} = $client->generateId($size = 21);
        });
    }

    /**
     * @param  mixed  $value
     * @param  string  $locale
     * @return float
     */
    public function formatToCurrency($value, $locale = 'en_US')
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($value, $this->currency_code);
    }

    /**
     * @description Returns the tax in % taken from the Configuration
     *
     * @return int
     */
    public function getTaxPercent()
    {
        $tax = config('SETTINGS::PAYMENTS:SALES_TAX');

        return $tax < 0 ? 0 : $tax;
    }

    public function getPriceAfterDiscount()
    {
        return number_format($this->price - ($this->price * PartnerDiscount::getDiscount() / 100), 2);
    }

    /**
     * @description Returns the tax as Number
     *
     * @return float
     */
    public function getTaxValue()
    {
        return number_format($this->getPriceAfterDiscount() * $this->getTaxPercent() / 100, 2);
    }

    /**
     * @description Returns the full price of a Product including tax
     *
     * @return float
     */
    public function getTotalPrice()
    {
        return number_format($this->getPriceAfterDiscount() + $this->getTaxValue(), 2);
    }
}
