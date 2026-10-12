<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\ApplicationApiControllerTest;

use InvalidArgumentException;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Tests\TestCase;
use Pterodactyl\Transformers\Api\Admin\UserTransformer as AdminUserTransformer;
use Pterodactyl\Transformers\Api\Application\BaseTransformer;
use Pterodactyl\Transformers\Api\Application\UserTransformer;

uses(TestCase::class);

test('the Application API refuses admin transformers, which return every extension field', function (): void {
    $controller = new class extends ApplicationApiController {};

    expect(fn (): BaseTransformer => $controller->getTransformer(AdminUserTransformer::class))->toThrow(InvalidArgumentException::class, 'does not belong to the Application API');
});

test('the Application API resolves its own transformers', function (): void {
    $controller = new class extends ApplicationApiController {};

    expect($controller->getTransformer(UserTransformer::class))->toBeInstanceOf(UserTransformer::class);
});
