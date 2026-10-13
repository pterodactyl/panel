<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Variables;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Eggs\ReordersEggVariables;
use Pterodactyl\Models\Egg;

final readonly class ReorderEggVariables implements ReordersEggVariables
{
    /** @return NormalizedValidationRules */
    public static function rules(): array
    {
        return [
            'order' => ['required', 'array', 'list'],
            'order.*' => ['required', Rule::anyOf([['integer:strict'], ['string']]), 'integer', 'distinct'],
        ];
    }

    /**
     * @param  list<mixed>  $order
     * @return list<int>
     */
    public static function order(array $order): array
    {
        $input = Validator::make(['order' => $order], self::rules())->safe();

        return array_map(fn (int|string $key): int => $input->integer('order.'.$key), array_keys($input->array('order')));
    }

    public function reorder(Egg $egg, array $order): void
    {
        $order = self::order($order);

        DB::transaction(function () use ($egg, $order): void {
            $egg = Egg::query()->lockForUpdate()->findOrFail($egg->id);
            foreach ($order as $index => $variableId) {
                $egg->variables()->whereKey($variableId)->update(['sort_order' => $index]);
            }
        });
    }
}
