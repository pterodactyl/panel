<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Actions\Servers\UpdateServerDetails;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Server;

class UpdateServerDetailsRequest extends ServerWriteRequest
{
    use ValidatesExtensionFields {
        actionInput as private inputWithExtensions;
    }

    /**
     * Rules to apply to a server details update request. The Application API keeps its own
     * field names; actionInput() hands the values to the action under its names.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = UpdateServerDetails::rules($this->parameter('server', Server::class));

        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'user' => $rules['owner_id'],
            'description' => $rules['description'],
        ];
    }

    /**
     * The validated input under the action's field names: user is its owner_id.
     *
     * @return array<string, mixed>
     */
    public function actionInput(): array
    {
        $input = $this->inputWithExtensions();
        $input['owner_id'] = $input['user'] ?? null;
        unset($input['user']);

        return $input;
    }

    /**
     * Rename some attributes in error messages to clarify the field
     * being discussed.
     */
    public function attributes(): array
    {
        return [
            'user' => 'User ID',
            'name' => 'Server Name',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Server
    {
        return $this->parameter('server', Server::class);
    }
}
