<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pterodactyl\Validation\EggRules;

/**
 * A newline-separated list of Docker images, as the admin egg form submits them. Each
 * line is held to the rule for a single image; a multi-line pattern would accept the
 * whole list as soon as one line matched.
 */
class DockerImageList implements ValidationRule
{
    /** @return list<string> */
    public static function lines(string $value): array
    {
        return array_values(array_filter(array_map(trim(...), explode("\n", $value)), fn (string $line): bool => $line !== ''));
    }

    /**
     * Describes the rule in the generated API documentation.
     *
     * @return array{description: string}
     */
    public function docs(): array
    {
        return ['description' => 'One image per line, optionally prefixed with a display name and a pipe (<code>Java 21|ghcr.io/pterodactyl/yolks:java_21</code>). Each must be at most 191 characters and match the regex /^[\w#\.\/\- ]*\|?~?[\w\.\/\-:@ ]*$/.'];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $images = self::lines($value);
        if ($images === []) {
            $fail('The :attribute field must list at least one image.');

            return;
        }

        foreach ($images as $image) {
            if (Validator::make(['image' => $image], ['image' => EggRules::DOCKER_IMAGE])->fails()) {
                $fail(sprintf('The :attribute entry "%s" is not a valid Docker image.', Str::limit($image, 64)));

                return;
            }
        }
    }
}
