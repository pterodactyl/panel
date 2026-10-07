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
    public function __construct(protected array $invalidPaths) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $path = Mount::normalizePath($value);

        foreach ($this->invalidPaths as $blocked) {
            $blocked = rtrim($blocked, '/');

            if ($path === $blocked || str_starts_with($path, $blocked . '/')) {
                $fail("The {$attribute} path cannot be or reside within [{$blocked}].");

                return;
            }
        }
    }
}

