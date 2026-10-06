<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Concerns;

use Pterodactyl\Services\Extensions\ExtensionFieldValueGuard;
use Pterodactyl\Services\Extensions\ExtensionFormFields;
use Pterodactyl\Support\JsonValueGuard;

trait ValidatesExtensionFields
{
    abstract public function extensionForm(): string;

    /**
     * @return ExtensionFormValues
     */
    public function extensionFields(): array
    {
        $validated = $this->validated('extensions');

        return is_array($validated) ? ExtensionFieldValueGuard::formValues(JsonValueGuard::jsonArray($validated)) : [];
    }

    /**
     * {@inheritdoc}
     */
    protected function validationRules(): array
    {
        $input = $this->input('extensions');
        $submitted = is_array($input) ? array_values(array_filter(array_keys($input), is_string(...))) : [];

        return array_merge(
            parent::validationRules(),
            $this->container->make(ExtensionFormFields::class)->rules($this->extensionForm(), $submitted),
        );
    }
}
