<?php

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
