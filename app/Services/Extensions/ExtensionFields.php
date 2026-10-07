<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Factory as ValidationFactory;
use Pterodactyl\Extensions\Attributes\ApplicationApi;
use Pterodactyl\Extensions\Fields;
use Pterodactyl\Rules\ExtensionFieldValue;
use ReflectionClass;
use Throwable;

/**
 * Runs the Fields extensions register for a model: authorizes and validates their
 * submitted values, saves them while the model is written, and reads them back. Each
 * Fields method is called through the container as `Class@method`, so it can type-hint
 * the model it extends and any other dependency.
 */
final readonly class ExtensionFields
{
    public function __construct(
        private Container $container,
        private ValidationFactory $validation,
        private ExtensionFieldRegistry $registry,
        private ExtensionRepository $extensions,
    ) {}

    /**
     * The Fields classes of running extensions for a model, keyed by extension id. The
     * Application API only sees those marked #[ApplicationApi].
     *
     * @param  class-string<Model>  $model
     * @return array<string, class-string<Fields>>
     */
    public function for(string $model, bool $applicationApi = false): array
    {
        return array_filter(
            $this->registry->for($model),
            fn (string $fields, string $extension): bool => $this->extensions->isAvailable($extension)
                && (! $applicationApi || (new ReflectionClass($fields))->getAttributes(ApplicationApi::class) !== []),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Refuses a request that sends values for fields the signed-in user may not change.
     *
     * @param  Model|class-string<Model>  $model  the model being updated, or the class being created
     * @param  list<string>  $submitted  the extensions the request sends values for
     *
     * @throws AuthorizationException
     */
    public function authorize(Model|string $model, array $submitted, bool $applicationApi = false): void
    {
        foreach (array_intersect_key($this->for($this->modelClass($model), $applicationApi), array_flip($submitted)) as $extension => $fields) {
            throw_unless($this->authorized($fields, $model), AuthorizationException::class, sprintf('You are not allowed to change the %s fields.', $extension));
        }
    }

    /**
     * Validates each extension's submitted values against its own rules, adding failures to
     * $validator as `extensions.<id>.<field>`. Values for extensions that are not running
     * are ignored, so their stored values stay as they are.
     *
     * @param  Model|class-string<Model>  $model  the model being updated, or the class being created
     * @param  array<string, array<array-key, ApiValue9>>  $submitted  each extension's submitted values
     * @return ExtensionFieldInput the validated values of each extension that sent any
     */
    public function validate(Validator $validator, Model|string $model, array $submitted, bool $applicationApi = false): array
    {
        $validated = [];
        foreach (array_intersect_key($this->for($this->modelClass($model), $applicationApi), $submitted) as $extension => $fields) {
            $rules = $this->rules($fields, $model);
            $own = $this->validation->make($submitted[$extension], $rules, $this->strings($fields, 'messages', $model), $this->strings($fields, 'attributes', $model));
            $own->addRules(array_fill_keys($this->fieldNames($rules), [new ExtensionFieldValue]));
            if ($own->fails()) {
                foreach ($own->errors()->getMessages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add(sprintf('extensions.%s.%s', $extension, $field), $message);
                    }
                }

                continue;
            }

            $validated[$extension] = ExtensionSettingValueGuard::fieldValues($own->validated());
        }

        return $validated;
    }

    /**
     * Saves the validated values an action received in its data's `extensions` entry. Call
     * it inside the transaction that writes the model, after the row is written.
     *
     * @param  ExtensionFieldInput  $values
     */
    public function save(Model $model, array $values): void
    {
        foreach (array_intersect_key($this->for($model::class), $values) as $extension => $fields) {
            if (method_exists($fields, 'save')) {
                $this->container->call($fields.'@save', [...$this->modelParameters($model), 'values' => $values[$extension]]);
            } else {
                $this->extensions->settings($extension)->for($model)->setMany($values[$extension]);
            }
        }
    }

    /**
     * The values of every extension the signed-in user may see, keyed by extension id. An
     * extension that fails to read them is recorded as failing and left out, so the admin
     * forms hide its fields rather than saving over its values.
     *
     * @return ExtensionFieldInput
     */
    public function values(Model $model, bool $applicationApi = false): array
    {
        $values = [];
        foreach ($this->for($model::class, $applicationApi) as $extension => $fields) {
            try {
                if ($this->authorized($fields, $model)) {
                    $values[$extension] = method_exists($fields, 'values')
                        ? ExtensionSettingValueGuard::fieldValues($this->container->call($fields.'@values', $this->modelParameters($model)))
                        : $this->stored($extension, $fields, $model);
                }
            } catch (Throwable $exception) {
                $this->extensions->recordFailure($extension, sprintf('Reading its %s fields failed: %s', class_basename($model), $exception->getMessage()), $exception, 'fields');
            }
        }

        return $values;
    }

    /**
     * The extensions whose fields each admin form shows to the signed-in user, keyed by
     * form name (`admin.user`, `admin.database_host`, ...).
     *
     * @return array<string, list<array{id: string, name: string}>>
     */
    public function forms(): array
    {
        $forms = [];
        foreach (ExtensionSettings::SCOPES as $alias => $model) {
            foreach ($this->for($model) as $extension => $fields) {
                if (rescue(fn (): bool => $this->authorized($fields, $model), false)) {
                    $forms['admin.'.$alias][] = ['id' => $extension, 'name' => $this->extensions->enabled()->get($extension)->name ?? $extension];
                }
            }
        }

        return $forms;
    }

    /**
     * The model for a parameter typed with its class: null while it is being created.
     *
     * @param  Model|class-string<Model>  $model
     * @return array<string, Model|null>
     */
    private function modelParameters(Model|string $model): array
    {
        $instance = $model instanceof Model ? $model : null;

        return [$this->modelClass($model) => $instance, Model::class => $instance];
    }

    /**
     * The fields rules name, without the nested keys of list fields (`tags.*`).
     *
     * @param  ValidationRules  $rules
     * @return list<string>
     */
    private function fieldNames(array $rules): array
    {
        return array_values(array_unique(array_map(fn (string $rule): string => explode('.', $rule, 2)[0], array_keys($rules))));
    }

    /**
     * @param  Model|class-string<Model>  $model
     * @return class-string<Model>
     */
    private function modelClass(Model|string $model): string
    {
        return $model instanceof Model ? $model::class : $model;
    }

    /**
     * Values the panel stores for an extension that has no values() and save(): one per
     * field named in its rules, null until set.
     *
     * @param  class-string<Fields>  $fields
     * @return ExtensionFieldValues
     */
    private function stored(string $extension, string $fields, Model $model): array
    {
        $settings = $this->extensions->settings($extension)->for($model);
        $values = [];
        foreach ($this->fieldNames($this->rules($fields, $model)) as $field) {
            // SAFETY: the panel only stores values that passed the ExtensionFieldValue rule.
            $values[$field] = ExtensionSettingValueGuard::fieldValue($settings->get($field));
        }

        return $values;
    }

    /**
     * @param  class-string<Fields>  $fields
     * @param  Model|class-string<Model>  $model
     */
    private function authorized(string $fields, Model|string $model): bool
    {
        return ! method_exists($fields, 'authorize') || $this->container->call($fields.'@authorize', $this->modelParameters($model)) === true;
    }

    /**
     * @param  class-string<Fields>  $fields
     * @param  Model|class-string<Model>  $model
     * @return ValidationRules
     */
    private function rules(string $fields, Model|string $model): array
    {
        return method_exists($fields, 'rules')
            ? ExtensionSettingValueGuard::validationRules($this->container->call($fields.'@rules', $this->modelParameters($model)))
            : [];
    }

    /**
     * The result of attributes() or messages().
     *
     * @param  class-string<Fields>  $fields
     * @param  'attributes'|'messages'  $method
     * @param  Model|class-string<Model>  $model
     * @return array<string, string>
     */
    private function strings(string $fields, string $method, Model|string $model): array
    {
        return method_exists($fields, $method)
            ? ExtensionSettingValueGuard::stringMap($this->container->call($fields.'@'.$method, $this->modelParameters($model)))
            : [];
    }
}
