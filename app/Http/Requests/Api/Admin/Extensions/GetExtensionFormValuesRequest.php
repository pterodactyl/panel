<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Extensions;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Services\Extensions\ExtensionFormFieldRegistry;
use Pterodactyl\Support\JsonValueGuard;

class GetExtensionFormValuesRequest extends AdminApiRequest
{
    /**
     * {@inheritdoc}
     */
    public function permissions(): array
    {
        $form = ExtensionFormFieldRegistry::FORMS[$this->form()] ?? null;

        return $form === null ? [] : [$form['permission']];
    }

    public function form(): string
    {
        return JsonValueGuard::string($this->route('form'));
    }

    public function subject(): Model
    {
        $model = ExtensionFormFieldRegistry::FORMS[$this->form()]['model'];

        return $model::query()->findOrFail(JsonValueGuard::integer($this->route('id')));
    }
}
