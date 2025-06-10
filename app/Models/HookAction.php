<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HookAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'hook_id', 'type', 'config', 'action_definition_id'
    ];

    public const RESOURCE_NAME = 'hook_action';


    protected $casts = [
        'config' => 'array',
    ];

    public function hook() {
        return $this->belongsTo(Hook::class);
    }
}
