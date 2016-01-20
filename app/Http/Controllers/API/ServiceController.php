<?php
/**
 * Pterodactyl Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Pterodactyl\Http\Controllers\API;

use Illuminate\Http\Request;

use Pterodactyl\Models;
use Pterodactyl\Transformers\ServiceTransformer;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Resource("Services")
 */
class ServiceController extends BaseController
{

    public function __construct()
    {
        //
    }

    public function getServices(Request $request)
    {
        return Models\Service::all();
    }

    public function getService(Request $request, $id)
    {
        $service = Models\Service::find($id);
        if (!$service) {
            throw new NotFoundHttpException('No service by that ID was found.');
        }

        $options = Models\ServiceOptions::select('id', 'name', 'description', 'tag', 'docker_image')->where('parent_service', $service->id)->get();
        foreach($options as &$opt) {
            $opt->variables = Models\ServiceVariables::where('option_id', $opt->id)->get();
        }

        return [
            'service' => $service,
            'options' => $options
        ];

    }

}
