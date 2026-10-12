<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Mounts\AttachesNodesToMounts;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;
use Pterodactyl\Validation\Concerns\ValidatesArrayExistence;

final readonly class AttachNodesToMount implements AttachesNodesToMounts
{
    use ValidatesArrayExistence;

    /** @return NormalizedValidationRules */
    public static function rules(): array
    {
        return [
            'nodes' => ['required', 'array', 'list', 'min:1'],
            'nodes.*' => [Rule::anyOf([['integer:strict'], ['string']]), 'required', 'integer'],
        ];
    }

    public function attach(Mount $mount, array $nodeIds): void
    {
        $mount->nodes()->syncWithoutDetaching($this->nodeIds($nodeIds));
    }

    /**
     * The nodes are looked up in one query, and a missing one fails `nodes.N` as an exists rule
     * would.
     *
     * @param  list<mixed>  $nodeIds
     * @return list<int>
     */
    private function nodeIds(array $nodeIds): array
    {
        $validator = Validator::make(['nodes' => $nodeIds], self::rules());
        $validator->after(fn () => $this->validateArrayExistence($validator, 'nodes', Node::query()));

        $input = $validator->safe();

        return array_map(fn (int|string $key): int => $input->integer('nodes.'.$key), array_keys($input->array('nodes')));
    }
}
