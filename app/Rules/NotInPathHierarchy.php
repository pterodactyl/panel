<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Pterodactyl\Models\Mount;

class NotInPathHierarchy implements ValidationRule
{
    /**
     * @param  list<string>  $invalidPaths
     */
    public function __construct(protected array $invalidPaths, protected bool $includeDescendants = true) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $path = Mount::normalizePath($value);

        foreach ($this->invalidPaths as $blocked) {
            $blocked = Mount::normalizePath($blocked);

            if ($path === $blocked) {
                $fail($this->includeDescendants
                    ? __('validation.internal.path_within', ['field' => $attribute, 'path' => $blocked])
                    : __('validation.internal.path_reserved', ['field' => $attribute, 'path' => $blocked]));

                return;
            }

            if ($this->includeDescendants && str_starts_with($path, $blocked.'/')) {
                $fail(__('validation.internal.path_within', ['field' => $attribute, 'path' => $blocked]));

                return;
            }
        }
    }
}
