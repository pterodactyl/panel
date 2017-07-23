<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Exceptions\DisplayValidationException;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Pterodactyl\Contracts\Repository\OptionVariableRepositoryInterface;

class VariableValidatorService
{
    /**
     * @var bool
     */
    protected $isAdmin = false;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $results = [];

    /**
     * @var \Pterodactyl\Contracts\Repository\OptionVariableRepositoryInterface
     */
    protected $optionVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    protected $serverVariableRepository;

    /**
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected $validator;

    /**
     * VariableValidatorService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\OptionVariableRepositoryInterface $optionVariableRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface         $serverRepository
     * @param \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \Illuminate\Contracts\Validation\Factory                            $validator
     */
    public function __construct(
        OptionVariableRepositoryInterface $optionVariableRepository,
        ServerRepositoryInterface $serverRepository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        ValidationFactory $validator
    ) {
        $this->optionVariableRepository = $optionVariableRepository;
        $this->serverRepository = $serverRepository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->validator = $validator;
    }

    /**
     * Set the fields with populated data to validate.
     *
     * @param  array $fields
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Set this function to be running at the administrative level.
     *
     * @return $this
     */
    public function setAdmin()
    {
        $this->isAdmin = true;

        return $this;
    }

    /**
     * Validate all of the passed data aganist the given service option variables.
     *
     * @param  int $option
     * @return $this
     */
    public function validate($option)
    {
        $variables = $this->optionVariableRepository->findWhere([['option_id', '=', $option]]);
        if (count($variables) === 0) {
            $this->results = [];

            return $this;
        }

        $variables->each(function ($item) {
            // Skip doing anything if user is not an admin and variable is not user viewable
            // or editable.
            if (! $this->isAdmin && (! $item->user_editable || ! $item->user_viewable)) {
                return;
            }

            $validator = $this->validator->make([
                'variable_value' => array_key_exists($item->env_variable, $this->fields) ? $this->fields[$item->env_variable] : null,
            ], [
                'variable_value' => $item->rules,
            ]);

            if ($validator->fails()) {
                throw new DisplayValidationException(json_encode(
                    collect([
                        'notice' => [
                            trans('admin/server.exceptions.bad_variable', ['name' => $item->name]),
                        ],
                    ])->merge($validator->errors()->toArray())
                ));
            }

            $this->results[] = [
                'id' => $item->id,
                'key' => $item->env_variable,
                'value' => $this->fields[$item->env_variable],
            ];
        });

        return $this;
    }

    /**
     * Return the final results after everything has been validated.
     *
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }
}
