<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Factory as ValidationFactory;
use Pterodactyl\Extensions\Fields;
use Pterodactyl\Rules\ExtensionFieldValue;
use Pterodactyl\Support\JsonValueGuard;
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
            $this->registry->for($model, $applicationApi),
            $this->extensions->isAvailable(...),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * The running extensions whose fields the signed-in user may change on a model. One
     * whose authorize() throws is recorded as failing and left out.
     *
     * @param  Model|class-string<Model>  $model  the model being updated, or the class being created
     * @return list<string>
     */
    public function authorizedFor(Model|string $model, bool $applicationApi = false): array
    {
        $authorized = [];
        foreach ($this->for($this->modelClass($model), $applicationApi) as $extension => $fields) {
            try {
                if ($this->authorized($fields, $model)) {
                    $authorized[] = $extension;
                }
            } catch (Throwable $exception) {
                $this->extensions->recordFailure($extension, sprintf('Authorizing its %s fields failed with %s; the log has the details.', class_basename($this->modelClass($model)), class_basename($exception)), $exception, 'fields');
            }
        }

        return $authorized;
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
     * are ignored, so their stored values stay as they are. Each extension in $defaults
     * that $submitted leaves out is validated with no values, so its required rules hold,
     * and nothing is saved for it. A secret field sent as its mask or not at all keeps its
     * stored value. An extension whose fields throw while validating is recorded as failing
     * and its values are refused.
     *
     * @param  Model|class-string<Model>  $model  the model being updated, or the class being created
     * @param  array<string, array<array-key, ApiValue9>>  $submitted  each extension's submitted values
     * @param  list<string>  $defaults  extensions to validate even when $submitted leaves them out
     */
    public function validate(Validator $validator, Model|string $model, array $submitted, bool $applicationApi = false, array $defaults = []): ValidatedExtensionValues
    {
        $values = $submitted + array_fill_keys($defaults, []);
        $validated = [];
        foreach (array_intersect_key($this->for($this->modelClass($model), $applicationApi), $values) as $extension => $fields) {
            try {
                [$errors, $checked] = $this->check($extension, $fields, $model, $values[$extension]);
            } catch (Throwable $exception) {
                // The message can carry query bindings or credentials, so only the log gets it.
                $this->extensions->recordFailure($extension, sprintf('Validating its %s fields failed with %s; the log has the details.', class_basename($this->modelClass($model)), class_basename($exception)), $exception, 'fields');
                $validator->errors()->add('extensions.'.$extension, sprintf('The %s fields could not be read, so they were not saved.', $extension));

                continue;
            }

            if ($errors !== []) {
                foreach ($errors as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add(sprintf('extensions.%s.%s', $extension, $field), $message);
                    }
                }

                continue;
            }

            if (array_key_exists($extension, $submitted)) {
                $validated[$extension] = $checked;
            }
        }

        return ValidatedExtensionValues::fromValidation($validated);
    }

    /**
     * Saves the validated values an action received in its data's `extensions` entry. Call
     * it inside the transaction that writes the model, after the row is written.
     */
    public function save(Model $model, ValidatedExtensionValues $values): void
    {
        $values = $values->all();
        foreach (array_intersect_key($this->for($model::class), $values) as $extension => $fields) {
            if (method_exists($fields, 'save')) {
                $this->container->call($fields.'@save', [...$this->modelParameters($model), 'values' => $values[$extension]]);
            } else {
                $this->extensions->settings($extension)->fields($model)->setManySecrets($values[$extension], $this->secrets($fields, $model));
            }
        }
    }

    /**
     * The values of every extension the signed-in user may see, keyed by extension id, with
     * secret fields masked. An extension that fails to read them is recorded as failing and
     * left out, so the admin forms hide its fields rather than saving over its values.
     *
     * @return ExtensionFieldInput
     */
    public function values(Model $model, bool $applicationApi = false): array
    {
        $values = [];
        foreach ($this->for($model::class, $applicationApi) as $extension => $fields) {
            try {
                if ($this->authorized($fields, $model)) {
                    $values[$extension] = $this->masked($this->current($extension, $fields, $model), $this->secrets($fields, $model));
                }
            } catch (Throwable $exception) {
                // The message can carry query bindings or credentials, so only the log gets it.
                $this->extensions->recordFailure($extension, sprintf('Reading its %s fields failed with %s; the log has the details.', class_basename($model), class_basename($exception)), $exception, 'fields');
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
     * Checks one extension's values against its rules: the errors keyed by field, and the
     * validated values when there are none.
     *
     * @param  class-string<Fields>  $fields
     * @param  Model|class-string<Model>  $model
     * @param  array<array-key, ApiValue9>  $submitted
     * @return array{array<string, array<string>>, ExtensionFieldValues}
     */
    private function check(string $extension, string $fields, Model|string $model, array $submitted): array
    {
        $rules = $this->rules($fields, $model);
        $values = $this->withStoredSecrets($extension, $fields, $model, $submitted);
        $own = $this->validation->make($values, $rules, $this->strings($fields, 'messages', $model), $this->strings($fields, 'attributes', $model));
        $own->addRules(array_fill_keys($this->fieldNames($rules), [new ExtensionFieldValue]));

        return $own->fails()
            ? [$own->errors()->getMessages(), []]
            : [[], ExtensionSettingValueGuard::fieldValues($own->validated())];
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
     * The current values of one extension's fields, unmasked.
     *
     * @param  class-string<Fields>  $fields
     * @return ExtensionFieldValues
     */
    private function current(string $extension, string $fields, Model $model): array
    {
        return method_exists($fields, 'values')
            ? ExtensionSettingValueGuard::fieldValues($this->container->call($fields.'@values', $this->modelParameters($model)))
            : $this->stored($extension, $fields, $model);
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
        $settings = $this->extensions->settings($extension)->fields($model);
        $values = [];
        foreach ($this->fieldNames($this->rules($fields, $model)) as $field) {
            // SAFETY: the panel only stores values that passed the ExtensionFieldValue rule.
            $values[$field] = ExtensionSettingValueGuard::fieldValue($settings->get($field));
        }

        return $values;
    }

    /**
     * The submitted values with each secret field sent as its mask or not at all replaced
     * by its stored value, so the rules check the real value and saving keeps it. With
     * nothing stored, or when creating, such a field is left out. A secret sent as null or
     * empty is kept as sent, so saving clears it.
     *
     * @param  class-string<Fields>  $fields
     * @param  Model|class-string<Model>  $model
     * @param  array<array-key, ApiValue9>  $submitted
     * @return array<array-key, ApiValue9>
     */
    private function withStoredSecrets(string $extension, string $fields, Model|string $model, array $submitted): array
    {
        $secrets = $this->secrets($fields, $model);
        if ($secrets === []) {
            return $submitted;
        }

        $stored = null;
        foreach ($secrets as $field) {
            if (array_key_exists($field, $submitted) && $submitted[$field] !== ExtensionSettingDefinition::MASK) {
                continue;
            }

            $stored ??= $model instanceof Model ? $this->current($extension, $fields, $model) : [];
            if (in_array($stored[$field] ?? null, [null, ''], true)) {
                unset($submitted[$field]);
            } else {
                $submitted[$field] = $stored[$field];
            }
        }

        return $submitted;
    }

    /**
     * @param  ExtensionFieldValues  $values
     * @param  list<string>  $secrets
     * @return ExtensionFieldValues
     */
    private function masked(array $values, array $secrets): array
    {
        foreach ($secrets as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = in_array($values[$field], [null, ''], true) ? null : ExtensionSettingDefinition::MASK;
            }
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
     * The fields secrets() names.
     *
     * @param  class-string<Fields>  $fields
     * @param  Model|class-string<Model>  $model
     * @return list<string>
     */
    private function secrets(string $fields, Model|string $model): array
    {
        return method_exists($fields, 'secrets')
            ? JsonValueGuard::stringList($this->container->call($fields.'@secrets', $this->modelParameters($model)))
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
