<?php

namespace Pterodactyl\Http\Controllers\Auth;

use OAuth2;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\MessageBag;
use Illuminate\Contracts\Hashing\Hasher;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class OAuth2Controller extends Controller
{
    /**
     * @var \Pterodactyl\Services\Users\UserCreationService
     */
    protected $creationService;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    private $hasher;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $auth;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    protected $updateService;

    /**
     * OAuth2Controller constructor.
     *
     * @param \Pterodactyl\Services\Users\UserCreationService           $creationService
     * @param \Illuminate\Contracts\Hashing\Hasher                      $hasher
     * @param \Illuminate\Auth\AuthManager                              $auth
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     * @param \Pterodactyl\Services\Users\UserUpdateService             $updateService
     */
    public function __construct(UserCreationService $creationService, Hasher $hasher, AuthManager $auth, UserRepositoryInterface $repository, UserUpdateService $updateService)
    {
        $this->creationService = $creationService;
        $this->hasher = $hasher;
        $this->auth = $auth;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * OAuth2 Login.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|mixed
     * @throws DataValidationException
     */
    public function login(Request $request)
    {
        if ($this->auth->guard()->check()) {
            return redirect()->route('index');
        }
        if (! env('OAUTH2_CLIENT_ID')) {
            abort(404);
        }
        try {
            $accessToken = OAuth2::getAccessToken();
        } catch (IdentityProviderException $e) {
            return abort(400, $e->getMessage());
        }

        try {
            if ($accessToken->hasExpired()) {
                OAuth2::refreshAccessToken();
            }
        } catch (\Exception $exception) {
        }

        try {
            if (env('OAUTH2_UPDATE_USER', true)) {
                $resources = OAuth2::getResources(null, null, null, true);
            } else {
                $resources = OAuth2::getResources();
            }
        } catch (\Exception $e) {
            return abort(400, $e->getMessage());
        }
        // Get the user's Id.
        $oauth2_id = $resources[env('OAUTH2_ID_KEY', 'id')];

        // Login the user if he already exists
        try {
            $user = User::where('oauth2_id', '=', $oauth2_id)->firstOrFail();

            // Update the user's details if enabled
            if (env('OAUTH2_UPDATE_USER', true) == true) {
                $email = $resources[env('OAUTH2_EMAIL_KEY', 'email')];
                $username = $resources[env('OAUTH2_USERNAME_KEY', 'username')];
                $name_first = $user->getAttributes()['name_first'];
                if (env('OAUTH2_FIRST_NAME_KEY') != null) {
                    $name_first = $resources[env('OAUTH2_FIRST_NAME_KEY')];
                }

                $name_last = $user->getAttributes()['name_last'];
                if (env('OAUTH2_LAST_NAME_KEY') != null) {
                    $name_last = $resources[env('OAUTH2_LAST_NAME_KEY')];
                }
                try {
                    $this->updateService->handle($user, compact('email', 'username', 'name_first', 'name_last'));
                } catch (\Exception $e) {
                    abort(500, $e->getMessage());
                }
            }
            // Login
            $this->auth->guard()->login($user);
            if ($this->auth->guard()->check()) {
                return redirect()->route('index');
            }
        } catch (\Exception $e) {

            // Create a new user using the OAuth2 provided info if enabled
            if (env('OAUTH2_CREATE_USER', false) == true) {
                $email = $resources[env('OAUTH2_EMAIL_KEY', 'email')];
                $username = $resources[env('OAUTH2_USERNAME_KEY', 'username')];

                $name_first = __('base.account.first_name');
                if (env('OAUTH2_FIRST_NAME_KEY') != null) {
                    $name_first = $resources[env('OAUTH2_FIRST_NAME_KEY')];
                }

                $name_last = __('base.account.last_name');
                if (env('OAUTH2_LAST_NAME_KEY') != null) {
                    $name_last = $resources[env('OAUTH2_LAST_NAME_KEY')];
                }

                $root_admin = false;

                $user = $this->creationService->handle(compact('email', 'username', 'name_first', 'name_last', 'root_admin', 'oauth2_id'));

                // Login
                $this->auth->guard()->login($user);

                if ($this->auth->guard()->check()) {
                    return redirect()->route('index');
                }

                return abort(500, 'Could not create a user for OAuth2 with the oauth2_id: ' . $oauth2_id . '.');
            }

            // Invalid Login
            OAuth2::forgetCachedData();

            $errors = new MessageBag(['user' => [__('auth.failed')]]);

            return redirect()->route('auth.login')
                ->withErrors($errors);
        }

        OAuth2::forgetCachedData();

        $errors = new MessageBag(['user' => [__('auth.failed')]]);

        return redirect()->route('auth.login')
            ->withErrors($errors);
    }

    public function callback()
    {
        if ($this->auth->guard()->check()) {
            return redirect()->route('index');
        }
        if (! env('OAUTH2_CLIENT_ID')) {
            abort(404);
        }
        OAuth2::AuthorizationCodeCallback();

        return redirect()->route('auth.oauth2');
    }
}
