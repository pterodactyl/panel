<?php

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\Server;
use Illuminate\Validation\Rule;

class ServerFormRequest extends AdminFormRequest
{
    /**
     * Rules to be applied to this request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = Server::getRules();
        $rules['description'][] = 'nullable';
        $rules['custom_image'] = 'sometimes|nullable|string';

        return $rules;
    }

    /**
     * Run validation after the rules above have been applied.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $validator->sometimes('node_id', 'required|numeric|bail|exists:nodes,id', function ($input) {
                return !($input->auto_deploy);
            });

            $validator->sometimes('allocation_id', [
                'required',
                'numeric',
                'bail',
                Rule::exists('allocations', 'id')->where(function ($query) {
                    $query->where('node_id', $this->input('node_id'));
                    $query->whereNull('server_id');
                }),
            ], function ($input) {
                return !($input->auto_deploy);
            });

            $validator->sometimes('allocation_additional.*', [
                'sometimes',
                'required',
                'numeric',
                Rule::exists('allocations', 'id')->where(function ($query) {
                    $query->where('node_id', $this->input('node_id'));
                    $query->whereNull('server_id');
                }),
            ], function ($input) {
                return !($input->auto_deploy);
            });
        });
    }
}
