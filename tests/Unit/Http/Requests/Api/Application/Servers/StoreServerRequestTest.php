<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Requests\Api\Application\Servers\StoreServerRequestTest;

use Illuminate\Support\Facades\Validator;
use Pterodactyl\Http\Requests\Api\Application\Servers\StoreServerRequest;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('deployment dedicated ip must be boolean', function () {
    $rule = (new StoreServerRequest())->rules()['deploy.dedicated_ip'];
    $validator = Validator::make(['deploy' => ['dedicated_ip' => 'not-a-boolean']], ['deploy.dedicated_ip' => $rule]);
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->toArray())->toHaveKey('deploy.dedicated_ip');
});
test('deployment tags accept integer ids and string slugs', function () {
    $rule = (new StoreServerRequest())->rules()['deploy.tags.*'];
    $validator = Validator::make(['deploy' => ['tags' => [123, 'minecraft']]], ['deploy.tags.*' => $rule]);
    expect($validator->fails())->toBeFalse();
});
