<?php

namespace Pterodactyl\Models;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $public_key
 * @property \Carbon\CarbonImmutable $created_at
 */
class UserSSHKey extends Model
{
    protected $table = 'user_ssh_keys';
    protected bool $immutableDates = true;

    /**
     * @var string[]
     */
    protected $guarded = ['id', 'created_at'];

    public static array $validationRules = [
        'name' => 'required|string',
        'public_key' => 'required|string',
    ];
}
