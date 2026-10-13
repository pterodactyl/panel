<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Http\Requests\FiltersDeployTagsTest;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Http\Requests\Concerns\FiltersDeployTags;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class);
uses(DatabaseTransactions::class);

test('deploy tags match slugs without regard to case', function () {
    $tag = Tag::factory()->create(['slug' => 'premium']);
    $request = new class extends FormRequest
    {
        use FiltersDeployTags;

        public function check(ValidatorContract $validator): void
        {
            $this->validateDeployTagsExist($validator);
        }
    };
    $request->merge(['deploy' => ['tags' => ['PREMIUM', (string) $tag->id, 'missing']]]);
    $validator = Validator::make([], []);

    $request->check($validator);

    expect($validator->errors()->get('deploy.tags'))->toBe(["The node tag 'missing' does not exist."]);
});
