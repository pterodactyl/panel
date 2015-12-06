<?php

namespace Pterodactyl\Models;

use Google2FA;
use Pterodactyl\Exceptions\AccountNotFoundException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Permission;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token', 'totp_secret'];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * Sets the TOTP secret for an account.
     *
     * @param int $id Account ID for which we want to generate a TOTP secret
     * @return string
     */
    public static function setTotpSecret($id)
    {

        $totpSecretKey = Google2FA::generateSecretKey();

        $user = User::find($id);

        if (!$user) {
            throw new AccountNotFoundException('An account with that ID (' . $id . ') does not exist in the system.');
        }

        $user->totp_secret = $totpSecretKey;
        $user->save();

        return $totpSecretKey;

    }

    /**
     * Enables or disables TOTP on an account if the token is valid.
     *
     * @param int $id Account ID for which we want to generate a TOTP secret
     * @return boolean
     */
    public static function toggleTotp($id, $token)
    {

        $user = User::find($id);

        if (!$user) {
            throw new AccountNotFoundException('An account with that ID (' . $id . ') does not exist in the system.');
        }

        if (!Google2FA::verifyKey($user->totp_secret, $token)) {
            return false;
        }

        $user->use_totp = ($user->use_totp === 1) ? 0 : 1;
        $user->save();

        return true;

    }

    /**
     * Set a user password to a new value assuming it meets the following requirements:
     * 		- 8 or more characters in length
     * 		- at least one uppercase character
     * 		- at least one lowercase character
     * 		- at least one number
     *
     * @param int $id The ID of the account to update the password on.
     * @param string $password The raw password to set the account password to.
     * @param string $regex The regex to use when validating the password. Defaults to '((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})'.
     * @return void
     */
    public static function setPassword($id, $password, $regex = '((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})')
    {

        $user = User::find($id);
        if (!$user) {
            throw new AccountNotFoundException('An account with that ID (' . $id . ') does not exist in the system.');
        }

        if (!preg_match($regex, $password)) {
            throw new DisplayException('The password passed did not meet the minimum password requirements.');
        }

        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->save();

        return;

    }

    /**
     * Updates the email address for an account.
     *
     * @param int $id
     * @param string $email
     * @return void
     */
    public static function setEmail($id, $email)
    {

        $user = User::find($id);
        if (!$user) {
            throw new AccountNotFoundException('An account with that ID (' . $id . ') does not exist in the system.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DisplayException('The email provided (' . $email . ') was not valid.');
        }

        $user->email = $email;
        $user->save();

        return;

    }

}
