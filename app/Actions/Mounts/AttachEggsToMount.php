<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Mounts\AttachesEggsToMounts;
use Pterodactyl\Http\Requests\Concerns\ValidatesArrayExistence;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Mount;

final readonly class AttachEggsToMount implements AttachesEggsToMounts
{
    use ValidatesArrayExistence;

    /**
     * The eggs to attach: a list of egg ids, each an integer or a numeric string.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'eggs' => ['required', 'array', 'list', 'min:1'],
            'eggs.*' => [Rule::anyOf([['integer:strict'], ['string']]), 'required', 'integer'],
        ];
    }

    /**
     * Attach eggs to a mount, keeping any eggs already attached.
     *
     * @param  list<ApiValue9>  $eggIds
     */
    public function attach(Mount $mount, array $eggIds): void
    {
        $mount->eggs()->syncWithoutDetaching($this->eggIds($eggIds));
    }

    /**
     * Validates the egg ids under `eggs` and returns them as integers. Each id must name an
     * egg; they are all looked up in one query, and a missing one fails `eggs.N` as an exists
     * rule would.
     *
     * @param  list<ApiValue9>  $eggIds
     * @return list<int>
     */
    private function eggIds(array $eggIds): array
    {
        $validator = Validator::make(['eggs' => $eggIds], self::rules());
        $validator->after(fn () => $this->validateArrayExistence($validator, 'eggs', Egg::query()));

        $input = $validator->safe();

        return array_map(fn (int|string $key): int => $input->integer('eggs.'.$key), array_keys($input->array('eggs')));
    }
}
