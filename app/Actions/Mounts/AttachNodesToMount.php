<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Mounts\AttachesNodesToMounts;
use Pterodactyl\Http\Requests\Concerns\ValidatesArrayExistence;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;

final readonly class AttachNodesToMount implements AttachesNodesToMounts
{
    use ValidatesArrayExistence;

    /**
     * The nodes to attach: a list of node ids, each an integer or a numeric string.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'nodes' => ['required', 'array', 'list', 'min:1'],
            'nodes.*' => [Rule::anyOf([['integer:strict'], ['string']]), 'required', 'integer'],
        ];
    }

    /**
     * Attach nodes to a mount, keeping any nodes already attached.
     *
     * @param  list<ApiValue9>  $nodeIds
     */
    public function attach(Mount $mount, array $nodeIds): void
    {
        $mount->nodes()->syncWithoutDetaching($this->nodeIds($nodeIds));
    }

    /**
     * Validates the node ids under `nodes` and returns them as integers. Each id must name a
     * node; they are all looked up in one query, and a missing one fails `nodes.N` as an
     * exists rule would.
     *
     * @param  list<ApiValue9>  $nodeIds
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
