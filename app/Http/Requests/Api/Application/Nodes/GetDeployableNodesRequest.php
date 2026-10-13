<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Closure;
use Illuminate\Validation\Validator;
use Pterodactyl\Http\Requests\Concerns\FiltersDeployTags;

class GetDeployableNodesRequest extends GetNodesRequest
{
    use FiltersDeployTags;

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'memory' => ['required', 'integer', 'min:0'],
            'disk' => ['required', 'integer', 'min:0'],
            'location_ids' => ['sometimes', 'array'],
            'location_ids.*' => ['integer'],
            'tags' => ['sometimes', 'array', 'list'],
            'tags.*' => ['string'],
            'egg' => ['sometimes', 'integer', 'exists:eggs,id'],
        ];
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [$this->validateDeployTagsExist(...)];
    }

    /**
     * The deployment criteria, read with Laravel's typed accessors. Without a page, every
     * viable node is listed.
     *
     * @return array{memory: int, disk: int, location_ids: list<int>, tags: string[], egg: int|null, per_page: int|null, page: int|null}
     */
    public function payload(): array
    {
        $validated = $this->safe();

        return [
            'memory' => $validated->integer('memory'),
            'disk' => $validated->integer('disk'),
            'location_ids' => array_map(fn (int|string $key): int => $validated->integer('location_ids.'.$key), array_keys($validated->array('location_ids'))),
            'tags' => $this->resolveDeployTagSlugs(),
            'egg' => $validated->filled('egg') ? $validated->integer('egg') : null,
            'per_page' => $validated->filled('per_page') ? $validated->integer('per_page') : null,
            'page' => $validated->filled('page') ? $validated->integer('page') : null,
        ];
    }

    protected function deployTagsField(): string
    {
        return 'tags';
    }
}
