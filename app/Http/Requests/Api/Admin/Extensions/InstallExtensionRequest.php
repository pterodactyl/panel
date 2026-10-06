<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Extensions;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use UnexpectedValueException;

class InstallExtensionRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminExtensionsInstall];
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'package' => ['required', 'file', 'max:51200'],
            'enable' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'package' => 'extension package',
        ];
    }

    /**
     * @return array{package: UploadedFile, enable: bool}
     */
    public function payload(): array
    {
        $package = $this->file('package');
        throw_unless($package instanceof UploadedFile, UnexpectedValueException::class, 'Expected an uploaded extension package.');

        return [
            'package' => $package,
            'enable' => $this->boolean('enable', false),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('enable')) {
            return;
        }

        $enable = filter_var($this->input('enable'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($enable !== null) {
            $this->merge(['enable' => $enable]);
        }
    }
}
