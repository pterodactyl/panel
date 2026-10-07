<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Pterodactyl\Extensions\Fields;

/** The Fields classes extensions registered, keyed by model class and extension id. */
final class ExtensionFieldRegistry
{
    /** @var array<class-string<Model>, array<string, ExtensionFieldEntry>> */
    private array $fields = [];

    /**
     * @param  class-string<Model>  $model
     * @param  class-string  $fields
     */
    public function register(string $extension, string $model, string $fields, ExtensionRegistration $registration): void
    {
        throw_unless(in_array($model, ExtensionSettings::SCOPES, true), InvalidArgumentException::class, sprintf('Extension "%s" cannot add fields to %s: only users, servers, nodes, eggs, locations, mounts and database hosts accept them.', $extension, $model));
        throw_unless(is_subclass_of($fields, Fields::class), InvalidArgumentException::class, sprintf('Extension "%s" registered %s for %s, which does not extend %s.', $extension, $fields, class_basename($model), Fields::class));
        throw_if(method_exists($fields, 'values') !== method_exists($fields, 'save'), InvalidArgumentException::class, sprintf('%s must implement both values() and save(), or neither to let the panel store its values.', $fields));
        throw_if(isset($this->fields[$model][$extension]), InvalidArgumentException::class, sprintf('Extension "%s" registered fields for %s more than once.', $extension, class_basename($model)));

        $this->fields[$model][$extension] = ['fields' => $fields, 'registration' => $registration];
    }

    /**
     * The Fields classes of extensions whose provider booted, keyed by extension id.
     *
     * @param  class-string<Model>  $model
     * @return array<string, class-string<Fields>>
     */
    public function for(string $model): array
    {
        $fields = [];
        foreach ($this->fields[$model] ?? [] as $extension => $entry) {
            if ($entry['registration']->isActive()) {
                $fields[$extension] = $entry['fields'];
            }
        }

        return $fields;
    }

    /** @return array<class-string<Model>, array<string, ExtensionFieldEntry>> */
    public function snapshot(): array
    {
        return $this->fields;
    }

    /** @param array<class-string<Model>, array<string, ExtensionFieldEntry>> $fields */
    public function restore(array $fields): void
    {
        $this->fields = $fields;
    }
}
