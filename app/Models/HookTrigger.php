<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HookTrigger extends Model
{
    use HasFactory;

    protected $casts = [
        'config' => 'array',
    ];

    protected $fillable = [
        'hook_id', 'type', 'config'
    ];

    public function hook() {
        return $this->belongsTo(Hook::class);
    }

    public function definition() {
        return $this->belongsTo(TriggerDefinition::class);
    }
}


