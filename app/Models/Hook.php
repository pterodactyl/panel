<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hook extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id', 'name', 'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function trigger()
    {
        return $this->hasOne(HookTrigger::class);
    }

    public function action()
    {
        return $this->hasOne(HookAction::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
