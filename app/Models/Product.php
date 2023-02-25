<?php

namespace App\Models;

use Hidehalo\Nanoid\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory;
    use LogsActivity;
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            -> logOnlyDirty()
            -> logOnly(['*'])
            -> dontSubmitEmptyLogs();
    }
    public $incrementing = false;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function (Product $product) {
            $client = new Client();

            $product->{$product->getKeyName()} = $client->generateId($size = 21);
        });

        static::deleting(function (Product $product) {
            $product->nodes()->detach();
            $product->eggs()->detach();
        });
    }

    public function getHourlyPrice()
    {
        // calculate the hourly price with the billing period
        switch($this->billing_period) {
            case 'daily':
                return $this->price / 24;
            case 'weekly':
                return $this->price / 24 / 7;
            case 'monthly':
                return $this->price / 24 / 30;
            case 'quarterly':
                return $this->price / 24 / 30 / 3;
            case 'half-annually':
                return $this->price / 24 / 30 / 6;
            case 'annually':
                return $this->price / 24 / 365;
            default:
                return $this->price;
        }
    }

    public function getDailyPrice()
    {
        return $this->price / 30;
    }

    public function getWeeklyPrice()
    {
        return $this->price / 4;
    }

    /**
     * @return BelongsTo
     */
    public function servers()
    {
        return $this->belongsTo(Server::class, 'id', 'product_id');
    }

    /**
     * @return BelongsToMany
     */
    public function eggs()
    {
        return $this->belongsToMany(Egg::class);
    }

    /**
     * @return BelongsToMany
     */
    public function nodes()
    {
        return $this->belongsToMany(Node::class);
    }
}
