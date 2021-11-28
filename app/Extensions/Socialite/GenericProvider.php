<?php


namespace Pterodactyl\Extensions\Socialite;

use Illuminate\Http\Request;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class GenericProvider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'GENERIC';

    /**
     * {@inheritdoc}
     */
    protected $scopes;

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    protected $authUrl;

    protected $tokenUrl;

    protected $userUrl;

    public function __construct(Request $request, $clientId, $clientSecret, $redirectUrl, $guzzle = []) {
        parent::__construct($request, $clientId, $clientSecret, $redirectUrl, $guzzle);

        $this->scopes = config('pterodactyl.auth.oauth.generic.scopes', []);
        $this->scopeSeparator = config('pterodactyl.auth.oauth.generic.scope_separator', ' ');
        $this->authUrl = config('pterodactyl.auth.oauth.generic.url.auth', '');
        $this->tokenUrl = config('pterodactyl.auth.oauth.generic.url.token', '');
        $this->userUrl = config('pterodactyl.auth.oauth.generic.url.user', '');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            $this->authUrl,
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->tokenUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://discord.com/api/users/@me',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => 'Unsupported in Generic Driver',
            'name'     => 'Unsupported in Generic Driver',
            'email'    => 'Unsupported in Generic Driver',
            'avatar'   => 'Unsupported in Generic Driver',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code): array
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
