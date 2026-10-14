<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Integration\Http;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

abstract class HttpTestCase extends IntegrationTestCase
{
    use DatabaseTransactions;
}
