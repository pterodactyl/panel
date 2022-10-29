<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pterodactyl\Models\ReferralCode.
 *
 * @property int $user_id
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Pterodactyl\Models\User $user
 */
class ReferralCode extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'referral_code';

    /**
     * The length of referral codes.
     */
    public const CODE_LENGTH = 8;

    /**
     * The length of the code to store in the database.
     */
    public const KEY_LENGTH = 16;

    /**
     * The table associated with the model.
     */
    protected $table = 'referral_codes';

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'code',
    ];

    /**
     * Rules to protect against invalid data entry to DB.
     */
    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'code' => 'present|string|size:16',
    ];

    protected $dates = [
        self::CREATED_AT,
    ];

    /**
     * Returns the user this token is assigned to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generates a new code.
     */
    public static function generateCode(int $type): string
    {
        $prefix = self::getPrefixForType($type);

        return $prefix . Str::random(self::IDENTIFIER_LENGTH - strlen($prefix));
    }
}
