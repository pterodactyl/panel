<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Support\JsonValueGuard;

/**
 * Accepts the `extensions` input of a request that creates or updates a model extensions
 * add Fields to: refuses values the signed-in user may not change, validates each
 * extension's values against its own rules alongside the request's, and hands the
 * validated values to the action through payload()'s `extensions` key.
 */
trait ValidatesExtensionFields
{
    /** @var ExtensionFieldInput */
    private array $extensionValues = [];

    /**
     * The model this request updates, or its class when the request creates one.
     *
     * @return Model|class-string<Model>
     */
    abstract protected function extensionFieldsModel(): Model|string;

    /**
     * The validated values of each extension that sent any, keyed by extension id.
     *
     * @return ExtensionFieldInput
     */
    public function extensionValues(): array
    {
        return $this->extensionValues;
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultValidator(ValidationFactory $factory): Validator
    {
        $fields = $this->container->make(ExtensionFields::class);
        $model = $this->extensionFieldsModel();
        $input = $this->input('extensions');
        $applicationApi = $this instanceof ApplicationApiRequest;

        $fields->authorize($model, is_array($input) ? array_values(array_filter(array_keys($input), is_string(...))) : [], $applicationApi);

        $validator = parent::createDefaultValidator($factory);
        $validator->after(function (Validator $validator) use ($fields, $model, $applicationApi): void {
            $this->extensionValues = $fields->validate($validator, $model, $this->submittedExtensionValues($validator), $applicationApi);
        });

        return $validator;
    }

    /**
     * Each extension's submitted values, reporting input that is not keyed by extension
     * and then by field.
     *
     * @return array<string, array<array-key, ApiValue9>>
     */
    private function submittedExtensionValues(Validator $validator): array
    {
        $input = $this->input('extensions');
        if ($input === null) {
            return [];
        }

        if (! is_array($input) || ($input !== [] && array_is_list($input))) {
            $validator->errors()->add('extensions', 'The extensions field must be an object keyed by extension.');

            return [];
        }

        $submitted = [];
        foreach ($input as $extension => $values) {
            if (! is_string($extension)) {
                continue;
            }

            if (! is_array($values) || ($values !== [] && array_is_list($values))) {
                $validator->errors()->add('extensions.'.$extension, sprintf('The %s values must be an object keyed by field.', $extension));

                continue;
            }

            $submitted[$extension] = JsonValueGuard::jsonArray($values);
        }

        return $submitted;
    }
}
