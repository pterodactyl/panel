<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $referrer_id
 * @property int $user_id
 * @property string $code_used
 */
class ReferralUses extends Model
{
    use HasFactory;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'referral_uses';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [
        'id',
        'timestamp',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'code_used',
        'referrer_id',
    ];

    /**
     * Rules to protect against invalid data entry to DB.
     */
    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'code_used' => 'required|string|size:16',
        'referrer_id' => 'required|exists:users,id',
    ];
}
