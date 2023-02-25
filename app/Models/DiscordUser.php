<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'username',
        'avatar',
        'discriminator',
        'public_flags',
        'flags',
        'locale',
        'mfa_enabled',
        'premium_type',
        'email',
        'verified',
    ];

    public $incrementing = false;

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return string
     */
    public function getAvatar()
    {
        return 'https://cdn.discordapp.com/avatars/'.$this->id.'/'.$this->avatar.'.png';
    }
}
