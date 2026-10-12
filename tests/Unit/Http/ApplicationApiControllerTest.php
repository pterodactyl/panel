<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\ApplicationApiControllerTest;

use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Tests\TestCase;
use Pterodactyl\Transformers\Api\Application\UserTransformer;

uses(TestCase::class);

test('the Application API resolves its own transformers', function (): void {
    $controller = new class extends ApplicationApiController {};

    expect($controller->getTransformer(UserTransformer::class))->toBeInstanceOf(UserTransformer::class);
});
