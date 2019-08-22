<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Auth\ResetsPasswords;
use App\Contracts\Repository\UserRepositoryInterface;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * The URL to redirect users to after password reset.
     *
     * @var string
     */
    public $redirectTo = '/';

    /**
     * @var bool
     */
    protected $hasTwoFactor = false;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alerts;

    /**
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    private $dispatcher;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    private $hasher;

    /**
     * @var \App\Contracts\Repository\UserRepositoryInterface
     */
    private $userRepository;

    /**
     * ResetPasswordController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                         $alerts
     * @param \Illuminate\Contracts\Events\Dispatcher                   $dispatcher
     * @param \Illuminate\Contracts\Hashing\Hasher                      $hasher
     * @param \App\Contracts\Repository\UserRepositoryInterface $userRepository
     */
    public function __construct(AlertsMessageBag $alerts, Dispatcher $dispatcher, Hasher $hasher, UserRepositoryInterface $userRepository)
    {
        $this->alerts = $alerts;
        $this->dispatcher = $dispatcher;
        $this->hasher = $hasher;
        $this->userRepository = $userRepository;
    }

    /**
     * Return the rules used when validating password reset.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    }

    /**
     * Reset the given user's password. If the user has two-factor authentication enabled on their
     * account do not automatically log them in. In those cases, send the user back to the login
     * form with a note telling them their password was changed and to log back in.
     *
     * @param \Illuminate\Contracts\Auth\CanResetPassword|\App\Models\User $user
     * @param string                                                               $password
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    protected function resetPassword($user, $password)
    {
        $user = $this->userRepository->update($user->id, [
            'password' => $this->hasher->make($password),
            $user->getRememberTokenName() => Str::random(60),
        ]);

        $this->dispatcher->dispatch(new PasswordReset($user));

        // If the user is not using 2FA log them in, otherwise skip this step and force a
        // fresh login where they'll be prompted to enter a token.
        if (! $user->use_totp) {
            $this->guard()->login($user);
        }

        $this->hasTwoFactor = $user->use_totp;
    }

    /**
     * Get the response for a successful password reset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetResponse(Request $request, $response)
    {
        if ($this->hasTwoFactor) {
            $this->alerts->success('Your password was successfully updated. Please log in to continue.')->flash();
        }

        return redirect($this->hasTwoFactor ? route('auth.login') : $this->redirectPath())
            ->with('status', trans($response));
    }
}
