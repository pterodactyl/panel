<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;

final class ValidExtensionSettingColor implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ExtensionSettingValueGuard::color($value) === null) {
            $fail(__('validation.internal.extension_setting.color'));
        }
    }
}
