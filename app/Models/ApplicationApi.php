<?php

namespace App\Models;

use Hidehalo\Nanoid\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationApi extends Model
{
    use HasFactory;

    protected $fillable = ['token', 'memo', 'last_used'];

    protected $primaryKey = 'token';

    public $incrementing = false;

    protected $casts = [
        'last_used' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (ApplicationApi $applicationApi) {
            $client = new Client();

            $applicationApi->{$applicationApi->getKeyName()} = $client->generateId(48);
        });
    }

    public function updateLastUsed()
    {
        $this->update(['last_used' => now()]);
    }
}
