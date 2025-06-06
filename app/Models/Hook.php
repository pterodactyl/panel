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

    public function triggers()
    {
        return $this->hasMany(HookTrigger::class);
    }

    public function actions()
    {
        return $this->hasMany(HookAction::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
