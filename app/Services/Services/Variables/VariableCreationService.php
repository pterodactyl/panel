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

namespace Pterodactyl\Services\Services\Variables;

use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Models\ServiceVariable;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceVariable\ReservedVariableNameException;

class VariableCreationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $serviceOptionRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface
     */
    protected $serviceVariableRepository;

    public function __construct(
        ServiceOptionRepositoryInterface $serviceOptionRepository,
        ServiceVariableRepositoryInterface $serviceVariableRepository
    ) {
        $this->serviceOptionRepository = $serviceOptionRepository;
        $this->serviceVariableRepository = $serviceVariableRepository;
    }

    /**
     * Create a new variable for a given service option.
     *
     * @param int|\Pterodactyl\Models\ServiceOption $option
     * @param array                                 $data
     * @return \Pterodactyl\Models\ServiceVariable
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\ServiceVariable\ReservedVariableNameException
     */
    public function handle($option, array $data)
    {
        if ($option instanceof ServiceOption) {
            $option = $option->id;
        }

        if (in_array(strtoupper(array_get($data, 'env_variable')), explode(',', ServiceVariable::RESERVED_ENV_NAMES))) {
            throw new ReservedVariableNameException(sprintf(
                'Cannot use the protected name %s for this environment variable.',
                array_get($data, 'env_variable')
            ));
        }

        $options = array_get($data, 'options', []);

        return $this->serviceVariableRepository->create(array_merge([
            'option_id' => $option,
            'user_viewable' => in_array('user_viewable', $options),
            'user_editable' => in_array('user_editable', $options),
        ], $data));
    }
}
