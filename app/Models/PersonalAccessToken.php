<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Str;
use Laravel\Sanctum\Contracts\HasAbilities;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonalAccessToken extends Model implements HasAbilities
{
    use HasFactory;
    use SoftDeletes;

    public const RESOURCE_NAME = 'personal_access_token';

    /**
     * The length of the raw API token.
     */
    public const TOKEN_LENGTH = 32;

    /**
     * @var string[]
     */
    protected $casts = [
        'user_id' => 'int',
        'abilities' => 'json',
        'last_used_at' => 'datetime',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'description',
        'token',
        'token_id',
        'abilities',
    ];

    public static array $validationRules = [
        'token' => 'required|string',
        'token_id' => 'required|string|size:16',
        'description' => 'required|nullable|string|max:500',
        'last_used_at' => 'nullable|date',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Required for support with Laravel Sanctum.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @see \Laravel\Sanctum\Guard::supportsTokens()
     */
    public function tokenable()
    {
        return $this->user();
    }

    /**
     * Determine if the token has a given ability.
     *
     * @param string $ability
     *
     * @return bool
     */
    public function can($ability)
    {
        return in_array('*', $this->abilities) ||
            array_key_exists($ability, array_flip($this->abilities));
    }

    /**
     * Determine if the token is missing a given ability.
     *
     * @param string $ability
     *
     * @return bool
     */
    public function cant($ability)
    {
        return !$this->can($ability);
    }

    /**
     * Find the token instance matching the given token.
     *
     * @param string $token
     *
     * @return \Pterodactyl\Models\PersonalAccessToken|null
     */
    public static function findToken($token)
    {
        if (strpos($token, '_') === false) {
            return null;
        }

        $id = Str::substr($token, 0, 16);
        $token = Str::substr($token, strlen($id));

        return static::where('token_id', $id)->where('token', hash('sha256', $token))->first();
    }

    /**
     * Generates a new identifier for a personal access token.
     */
    public static function generateTokenIdentifier(): string
    {
        return 'ptdl_' . Str::random(11);
    }
}
