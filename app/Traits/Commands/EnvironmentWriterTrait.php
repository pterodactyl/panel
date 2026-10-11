<?php

declare(strict_types=1);

namespace Pterodactyl\Traits\Commands;

use Closure;
use Illuminate\Support\Fluent;
use Pterodactyl\Exceptions\PterodactylException;

trait EnvironmentWriterTrait
{
    /**
     * Escapes an environment value by looking for any characters that could
     * reasonably cause environment parsing issues. Those values are then wrapped
     * in quotes before being returned.
     */
    public function escapeEnvironmentValue(?string $value): string
    {
        if (($value) === null) {
            return '';
        }

        if (! preg_match('/^\"(.*)\"$/', $value) && preg_match('/([^\w.\-+\/])+/', $value)) {
            return sprintf('"%s"', addslashes($value));
        }

        return $value;
    }

    /**
     * Update the .env file for the application using the passed in values.
     *
     * @param  array<string, EnvironmentValue>  $values  keyed by environment variable name
     *
     * @throws PterodactylException
     */
    public function writeToEnvironment(array $values = []): void
    {
        $path = base_path('.env');
        throw_unless(file_exists($path), PterodactylException::class, 'Cannot locate .env file, was this software installed correctly?');

        $saveContents = file_get_contents($path);
        throw_if($saveContents === false, PterodactylException::class, 'Unable to read the contents of the .env file.');

        collect($values)->each(function ($value, $key) use (&$saveContents): void {
            $key = mb_strtoupper($key);
            // SAFETY: EnvironmentValue is restricted to JSON scalars, which have deterministic string representations for .env persistence.
            $saveValue = sprintf('%s=%s', $key, $this->escapeEnvironmentValue($value === null ? null : (string) $value));

            if (preg_match_all('/^'.$key.'=(.*)$/m', $saveContents) < 1) {
                $saveContents = $saveContents.PHP_EOL.$saveValue;
            } else {
                // preg_replace() only returns null on a PCRE engine failure (e.g. the
                // backtrack limit); keep the existing contents rather than losing them.
                $saveContents = preg_replace('/^'.$key.'=(.*)$/m', $saveValue, $saveContents) ?? $saveContents;
            }
        });

        file_put_contents($path, $saveContents);
    }

    /**
     * The answer to a prompt, or the option passed in its place. Symfony returns
     * either one untyped: a string, or null for a blank answer without a default.
     */
    protected function answer(Closure $prompt): ?string
    {
        $answer = Fluent::make(['value' => $prompt()]);

        return $answer->get('value') === null ? null : $answer->string('value')->toString();
    }

    /**
     * The value currently configured for a key, offered as a prompt's default.
     */
    protected function configured(string $key, int|string|null $default = null): ?string
    {
        $config = Fluent::make(config()->all());

        return $config->get($key, $default) === null ? null : $config->string($key, $default)->toString();
    }
}
