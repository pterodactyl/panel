<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin\Nests;

use Illuminate\View\View;
use Pterodactyl\Models\Egg;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;

class EggController extends Controller
{
    protected $repository;

    public function __construct(EggRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function view(Egg $egg): View
    {
        return view('admin.eggs.view', [
            'egg' => $egg,
        ]);
    }
}
