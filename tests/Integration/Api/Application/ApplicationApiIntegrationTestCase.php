<?php

namespace Pterodactyl\Tests\Integration\Api\Application;

use Pterodactyl\Models\User;
use Pterodactyl\Models\PersonalAccessToken;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pterodactyl\Tests\Traits\Integration\CreatesTestModels;
use Pterodactyl\Tests\Traits\Http\IntegrationJsonRequestAssertions;

abstract class ApplicationApiIntegrationTestCase extends IntegrationTestCase
{
    use CreatesTestModels;
    use DatabaseTransactions;
    use IntegrationJsonRequestAssertions;

    /**
     * @var \Pterodactyl\Models\User
     */
    private $user;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/vnd.pterodactyl.v1+json',
        'Content-Type' => 'application/json',
    ];

    /**
     * Bootstrap application API tests. Creates a default admin user and associated API key
     * and also sets some default headers required for accessing the API.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['root_admin' => true]);

        $this->createNewAccessToken();
    }

    /**
     * @return \Pterodactyl\Models\User
     */
    public function getApiUser(): User
    {
        return $this->user;
    }

    /**
     * Creates a new default API key and refreshes the headers using it.
     */
    protected function createNewAccessToken(array $abilities = ['*']): PersonalAccessToken
    {
        $token = $this->user->createToken('test', $abilities);

        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken);

        return $token->accessToken;
    }
}
