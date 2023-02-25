<?php

namespace App\Http\Controllers\Admin;

use App\Classes\Pterodactyl;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ServerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|Response
     */
    public function index()
    {
        return view('admin.servers.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  Server  $server
     * @return Response
     */
    public function show(Server $server)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Server  $server
     * @return Response
     */
    public function edit(Server $server)
    {
        // get all users from the database
        $users = User::all();

        return view('admin.servers.edit')->with([
            'server' => $server,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Server  $server
     */
    public function update(Request $request, Server $server)
    {
        $request->validate([
            'identifier' => 'required|string',
            'user_id' => 'required|integer',
        ]);


        if ($request->get('user_id') != $server->user_id) {
            // find the user
            $user = User::findOrFail($request->get('user_id'));

            // try to update the owner on pterodactyl
            try {
                $response = Pterodactyl::updateServerOwner($server, $user->pterodactyl_id);
                if ($response->getStatusCode() != 200) {
                    return redirect()->back()->with('error', 'Failed to update server owner on pterodactyl');
                }

                // update the owner on the database
                $server->user_id = $user->id;
            } catch (Exception $e) {
                return redirect()->back()->with('error', 'Internal Server Error');
            }
        }

        // update the identifier
        $server->identifier = $request->get('identifier');
        $server->save();

        return redirect()->route('admin.servers.index')->with('success', 'Server updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Server  $server
     * @return RedirectResponse|Response
     */
    public function destroy(Server $server)
    {
        try {
            $server->delete();

            return redirect()->route('admin.servers.index')->with('success', __('Server removed'));
        } catch (Exception $e) {
            return redirect()->route('admin.servers.index')->with('error', __('An exception has occurred while trying to remove a resource "') . $e->getMessage() . '"');
        }
    }

    /**
     * Cancel the Server billing cycle.
     *
     * @param Server $server
     * @return RedirectResponse|Response
     */
    public function cancel (Server $server)
    {
        try {
            error_log($server->update([
                'cancelled' => now(),
            ]));
            return redirect()->route('servers.index')->with('success', __('Server cancelled'));
        } catch (Exception $e) {
            return redirect()->route('servers.index')->with('error', __('An exception has occurred while trying to cancel the server"') . $e->getMessage() . '"');
        }
    }

    /**
     * @param Server $server
     * @return RedirectResponse
     */
    public function toggleSuspended(Server $server)
    {
        try {
            $server->isSuspended() ? $server->unSuspend() : $server->suspend();
        } catch (Exception $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', __('Server has been updated!'));
    }

    public function syncServers()
    {
        $pteroServers = Pterodactyl::getServers();
        $CPServers = Server::get();

        $CPIDArray = [];
        $renameCount = 0;
        foreach ($CPServers as $CPServer) { //go thru all CP servers and make array with IDs as keys. All values are false.
            if ($CPServer->pterodactyl_id) {
                $CPIDArray[$CPServer->pterodactyl_id] = false;
            }
        }

        foreach ($pteroServers as $server) { //go thru all ptero servers, if server exists, change value to true in array.
            if (isset($CPIDArray[$server['attributes']['id']])) {
                $CPIDArray[$server['attributes']['id']] = true;

                if (isset($server['attributes']['name'])) { //failsafe
                    //Check if a server got renamed
                    $savedServer = Server::query()->where('pterodactyl_id', $server['attributes']['id'])->first();
                    if ($savedServer->name != $server['attributes']['name']) {
                        $savedServer->name = $server['attributes']['name'];
                        $savedServer->save();
                        $renameCount++;
                    }
                }
            }
        }
        $filteredArray = array_filter($CPIDArray, function ($v, $k) {
            return $v == false;
        }, ARRAY_FILTER_USE_BOTH); //Array of servers, that dont exist on ptero (value == false)
        $deleteCount = 0;
        foreach ($filteredArray as $key => $CPID) { //delete servers that dont exist on ptero anymore
            if (!Pterodactyl::getServerAttributes($key, true)) {
                $deleteCount++;
            }
        }

        return redirect()->back()->with('success', __('Servers synced successfully' . (($renameCount) ? (',\n' . __('renamed') . ' ' . $renameCount . ' ' . __('servers')) : '') . ((count($filteredArray)) ? (',\n' . __('deleted') . ' ' . $deleteCount . '/' . count($filteredArray) . ' ' . __('old servers')) : ''))) . '.';
    }

    /**
     * @return JsonResponse|mixed
     *
     * @throws Exception
     */
    public function dataTable(Request $request)
    {
        $query = Server::with(['user', 'product']);
        if ($request->has('product')) {
            $query->where('product_id', '=', $request->input('product'));
        }
        if ($request->has('user')) {
            $query->where('user_id', '=', $request->input('user'));
        }
        $query->select('servers.*');

        return datatables($query)
            ->addColumn('user', function (Server $server) {
                return '<a href="' . route('admin.users.show', $server->user->id) . '">' . $server->user->name . '</a>';
            })
            ->addColumn('resources', function (Server $server) {
                return $server->product->description;
            })
            ->addColumn('actions', function (Server $server) {
                $suspendColor = $server->isSuspended() ? 'btn-success' : 'btn-warning';
                $suspendIcon = $server->isSuspended() ? 'fa-play-circle' : 'fa-pause-circle';
                $suspendText = $server->isSuspended() ? __('Unsuspend') : __('Suspend');

                return '
                         <a data-content="' . __('Edit') . '" data-toggle="popover" data-trigger="hover" data-placement="top"  href="' . route('admin.servers.edit', $server->id) . '" class="btn btn-sm btn-info mr-1"><i class="fas fa-pen"></i></a>
                        <form class="d-inline" method="post" action="' . route('admin.servers.togglesuspend', $server->id) . '">
                            ' . csrf_field() . '
                           <button data-content="' . $suspendText . '" data-toggle="popover" data-trigger="hover" data-placement="top" class="btn btn-sm ' . $suspendColor . ' text-white mr-1"><i class="far ' . $suspendIcon . '"></i></button>
                       </form>

                       <form class="d-inline" onsubmit="return submitResult();" method="post" action="' . route('admin.servers.destroy', $server->id) . '">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                           <button data-content="' . __('Delete') . '" data-toggle="popover" data-trigger="hover" data-placement="top" class="btn btn-sm btn-danger mr-1"><i class="fas fa-trash"></i></button>
                       </form>

                ';
            })
            ->addColumn('status', function (Server $server) {
                $labelColor = $server->isSuspended() ? 'text-danger' : 'text-success';

                return '<i class="fas ' . $labelColor . ' fa-circle mr-2"></i>';
            })
            ->editColumn('created_at', function (Server $server) {
                return $server->created_at ? $server->created_at->diffForHumans() : '';
            })
            ->editColumn('suspended', function (Server $server) {
                return $server->suspended ? $server->suspended->diffForHumans() : '';
            })
            ->editColumn('name', function (Server $server) {
                return '<a class="text-info" target="_blank" href="' . config('SETTINGS::SYSTEM:PTERODACTYL:URL') . '/admin/servers/view/' . $server->pterodactyl_id . '">' . strip_tags($server->name) . '</a>';
            })
            ->rawColumns(['user', 'actions', 'status', 'name'])
            ->make();
    }
}
