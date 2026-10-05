<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Variables;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Eggs\ReordersEggVariables;
use Pterodactyl\Models\Egg;

final readonly class ReorderEggVariables implements ReordersEggVariables
{
    /** @param list<int> $order */
    public function reorder(Egg $egg, array $order): void
    {
        DB::transaction(function () use ($egg, $order): void {
            $egg = Egg::query()->lockForUpdate()->findOrFail($egg->id);
            foreach ($order as $index => $variableId) {
                $egg->variables()->whereKey($variableId)->update(['sort_order' => $index]);
            }
        });
    }
}
