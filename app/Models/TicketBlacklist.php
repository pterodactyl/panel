<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketBlacklist extends Model
{
    protected $fillable = [
        'user_id', 'status', 'reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
