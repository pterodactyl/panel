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

namespace Pterodactyl\Services\Services\Options;

use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Exceptions\Services\ServiceOption\InvalidCopyFromException;

class InstallScriptUpdateService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $serviceOptionRepository;

    /**
     * InstallScriptUpdateService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface $serviceOptionRepository
     */
    public function __construct(ServiceOptionRepositoryInterface $serviceOptionRepository)
    {
        $this->serviceOptionRepository = $serviceOptionRepository;
    }

    /**
     * Modify the option install script for a given service option.
     *
     * @param  int|\Pterodactyl\Models\ServiceOption $option
     * @param  array                                 $data
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Services\ServiceOption\InvalidCopyFromException
     */
    public function handle($option, array $data)
    {
        if (! $option instanceof ServiceOption) {
            $option = $this->serviceOptionRepository->find($option);
        }

        if (! is_null(array_get($data, 'copy_script_from'))) {
            if (! $this->serviceOptionRepository->isCopiableScript(array_get($data, 'copy_script_from'), $option->service_id)) {
                throw new InvalidCopyFromException(trans('admin/exceptions.service.options.invalid_copy_id'));
            }
        }

        $this->serviceOptionRepository->withoutFresh()->update($option->id, [
            'script_install' => array_get($data, 'script_install'),
            'script_is_privileged' => array_get($data, 'script_is_privileged'),
            'script_entry' => array_get($data, 'script_entry'),
            'script_container' => array_get($data, 'script_container'),
            'copy_script_from' => array_get($data, 'copy_script_from'),
        ]);
    }
}
