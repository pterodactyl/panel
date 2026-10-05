<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;
use UnexpectedValueException;

final class ValidExtensionSettings implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            ExtensionSettingValueGuard::assertValues($value);
        } catch (UnexpectedValueException $unexpectedValueException) {
            $fail($unexpectedValueException->getMessage());
        }
    }
}
