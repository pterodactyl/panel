<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Concerns;

use UnexpectedValueException;

trait ParsesServerStartupData
{
    /** @return ServerStartupModificationData */
    protected function serverStartupData(string $eggField, string $imageField): array
    {
        $environment = $this->input('environment', []);
        throw_unless(is_array($environment), UnexpectedValueException::class, 'The validated server environment must be an array.');

        $variables = [];
        foreach ($environment as $key => $value) {
            throw_if(! is_string($key) || (! is_bool($value) && ! is_float($value) && ! is_int($value) && ! is_string($value) && $value !== null), UnexpectedValueException::class, 'Server environment variables must have string keys and scalar values.');

            $variables[$key] = $value;
        }

        return [
            'startup' => $this->string('startup')->toString(),
            'environment' => $variables,
            'skip_scripts' => $this->boolean('skip_scripts'),
            'egg_id' => $this->integer($eggField),
            'docker_image' => $this->string($imageField)->toString(),
        ];
    }
}
