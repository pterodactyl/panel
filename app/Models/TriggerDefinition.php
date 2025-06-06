<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TriggerDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'config_schema',
    ];

    protected $casts = [
        'config_schema' => 'array',
    ];
}
