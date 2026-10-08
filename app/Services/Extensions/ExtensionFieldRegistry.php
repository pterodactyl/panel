<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Contracts\Container\ContextualAttribute;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Pterodactyl\Extensions\Fields;
use ReflectionAttribute;
use ReflectionMethod;
use ReflectionNamedType;

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
        $this->assertModelParameters($model, $fields);

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

    /**
     * A parameter typed with a model receives the model the fields belong to, and every
     * method but values() and save() also runs while that model is created, before it
     * exists. A parameter of another model class, or one that does not accept null in those
     * methods, would therefore get an empty model or fail: the author almost always wanted
     * the signed-in user, which `#[CurrentUser] User $admin` provides.
     *
     * @param  class-string<Model>  $model
     * @param  class-string  $fields
     */
    private function assertModelParameters(string $model, string $fields): void
    {
        foreach (['authorize', 'rules', 'attributes', 'messages', 'secrets', 'values', 'save'] as $method) {
            if (! method_exists($fields, $method)) {
                continue;
            }

            foreach ((new ReflectionMethod($fields, $method))->getParameters() as $parameter) {
                $type = $parameter->getType();
                if (! $type instanceof ReflectionNamedType || ! is_a($type->getName(), Model::class, true) || $parameter->getAttributes(ContextualAttribute::class, ReflectionAttribute::IS_INSTANCEOF) !== []) {
                    continue;
                }

                $name = sprintf('%s::%s() parameter $%s', class_basename($fields), $method, $parameter->getName());
                throw_unless(in_array($type->getName(), [$model, Model::class], true), InvalidArgumentException::class, sprintf('%s would receive an empty %s: only the %s the fields belong to is passed. Use #[CurrentUser] User for the signed-in user.', $name, class_basename($type->getName()), class_basename($model)));
                throw_if(! in_array($method, ['values', 'save'], true) && ! $type->allowsNull(), InvalidArgumentException::class, sprintf('%s is null while the %s is created: make it nullable, or use #[CurrentUser] User for the signed-in user.', $name, class_basename($model)));
            }
        }
    }
}
