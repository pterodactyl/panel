<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

trait ValidatesArrayExistence
{
    /**
     * Preserve per-element Exists errors while checking the submitted IDs in one query.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    protected function validateArrayExistence(Validator $validator, string $attribute, Builder $query): void
    {
        $values = $validator->getData()[$attribute] ?? [];
        if (! is_array($values) || $validator->errors()->has($attribute)) {
            return;
        }

        $ids = [];
        foreach ($values as $index => $value) {
            if (! $validator->errors()->has($attribute.'.'.$index) && (is_int($value) || is_string($value))) {
                $ids[$index] = $value;
            }
        }

        if ($ids === []) {
            return;
        }

        $existing = $query->whereKey(array_values($ids))->pluck($query->getModel()->getKeyName())->all();
        foreach ($ids as $index => $id) {
            if (! in_array($id, $existing)) {
                $validator->addFailure($attribute.'.'.$index, 'Exists', [$query->getModel()->getTable(), $query->getModel()->getKeyName()]);
            }
        }
    }
}
