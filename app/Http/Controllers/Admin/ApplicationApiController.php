<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationApi;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApplicationApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|Response
     */
    public function index()
    {
        return view('admin.api.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View|Response
     */
    public function create()
    {
        return view('admin.api.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'memo' => 'nullable|string|max:60',
        ]);

        ApplicationApi::create([
            'memo' => $request->input('memo'),
        ]);

        return redirect()->route('admin.api.index')->with('success', __('api key created!'));
    }

    /**
     * Display the specified resource.
     *
     * @param  ApplicationApi  $applicationApi
     * @return Response
     */
    public function show(ApplicationApi $applicationApi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  ApplicationApi  $applicationApi
     * @return Application|Factory|View|Response
     */
    public function edit(ApplicationApi $applicationApi)
    {
        return view('admin.api.edit', [
            'applicationApi' => $applicationApi,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  ApplicationApi  $applicationApi
     * @return RedirectResponse
     */
    public function update(Request $request, ApplicationApi $applicationApi)
    {
        $request->validate([
            'memo' => 'nullable|string|max:60',
        ]);

        $applicationApi->update($request->all());

        return redirect()->route('admin.api.index')->with('success', __('api key updated!'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  ApplicationApi  $applicationApi
     * @return RedirectResponse
     */
    public function destroy(ApplicationApi $applicationApi)
    {
        $applicationApi->delete();

        return redirect()->back()->with('success', __('api key has been removed!'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse|mixed
     *
     * @throws Exception
     */
    public function dataTable(Request $request)
    {
        $query = ApplicationApi::query();

        return datatables($query)
            ->addColumn('actions', function (ApplicationApi $apiKey) {
                return '
                <a data-content="'.__('Edit').'" data-toggle="popover" data-trigger="hover" data-placement="top"  href="'.route('admin.api.edit', $apiKey->token).'" class="btn btn-sm btn-info mr-1"><i class="fas fa-pen"></i></a>
                <form class="d-inline" onsubmit="return submitResult();" method="post" action="'.route('admin.api.destroy', $apiKey->token).'">
                            '.csrf_field().'
                            '.method_field('DELETE').'
                           <button data-content="'.__('Delete').'" data-toggle="popover" data-trigger="hover" data-placement="top" class="btn btn-sm btn-danger mr-1"><i class="fas fa-trash"></i></button>
                       </form>
                ';
            })
            ->editColumn('token', function (ApplicationApi $apiKey) {
                return "<code>{$apiKey->token}</code>";
            })
            ->editColumn('last_used', function (ApplicationApi $apiKey) {
                return $apiKey->last_used ? $apiKey->last_used->diffForHumans() : '';
            })
            ->rawColumns(['actions', 'token'])
            ->make();
    }
}
