<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Mounts\AttachesEggsToMounts;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Mount;
use Pterodactyl\Validation\Concerns\ValidatesArrayExistence;

final readonly class AttachEggsToMount implements AttachesEggsToMounts
{
    use ValidatesArrayExistence;

    /** @return NormalizedValidationRules */
    public static function rules(): array
    {
        return [
            'eggs' => ['required', 'array', 'list', 'min:1'],
            'eggs.*' => [Rule::anyOf([['integer:strict'], ['string']]), 'required', 'integer'],
        ];
    }

    public function attach(Mount $mount, array $eggIds): void
    {
        $mount->eggs()->syncWithoutDetaching($this->eggIds($eggIds));
    }

    /**
     * The eggs are looked up in one query, and a missing one fails `eggs.N` as an exists rule
     * would.
     *
     * @param  list<mixed>  $eggIds
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
