<?php

namespace Pterodactyl\Http\Requests\Admin\Egg;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class EggFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'docker_images' => ['required', 'string', 'regex:/^[\w#\.\/\- ]*\|?~?[\w\.\/\-:@ ]*$/im'],
            'force_outgoing_ip' => 'sometimes|boolean',
            'file_denylist' => 'array',
            'features' => 'sometimes|array',
            'startup' => 'required|string',
            'config_from' => 'sometimes|bail|nullable|numeric',
            'config_stop' => 'required_without:config_from|nullable|string|max:191',
            'config_startup' => 'required_without:config_from|nullable|json',
            'config_logs' => 'required_without:config_from|nullable|json',
            'config_files' => 'required_without:config_from|nullable|json',
        ];

        if ($this->method() === 'POST') {
            $rules['nest_id'] = 'required|numeric|exists:nests,id';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->sometimes('config_from', 'exists:eggs,id', function () {
            return (int) $this->input('config_from') !== 0;
        });
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();

        return array_merge($data, [
            'force_outgoing_ip' => array_get($data, 'force_outgoing_ip', false),
            'features' => array_get($data, 'features', []),
        ]);
    }
}
