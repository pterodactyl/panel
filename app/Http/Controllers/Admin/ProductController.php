<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Nest;
use App\Models\Product;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        return view('admin.products.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        return view('admin.products.create', [
            'locations' => Location::with('nodes')->get(),
            'nests' => Nest::with('eggs')->get(),
        ]);
    }

    public function clone(Request $request, Product $product)
    {
        return view('admin.products.create', [
            'product' => $product,
            'locations' => Location::with('nodes')->get(),
            'nests' => Nest::with('eggs')->get(),
        ]);
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
            "name" => "required|max:30",
            "price" => "required|numeric|max:1000000|min:0",
            "memory" => "required|numeric|max:1000000|min:5",
            "cpu" => "required|numeric|max:1000000|min:0",
            "swap" => "required|numeric|max:1000000|min:0",
            "description" => "required|string|max:191",
            "disk" => "required|numeric|max:1000000|min:5",
            "minimum_credits" => "required|numeric|max:1000000|min:-1",
            "io" => "required|numeric|max:1000000|min:0",
            "databases" => "required|numeric|max:1000000|min:0",
            "backups" => "required|numeric|max:1000000|min:0",
            "allocations" => "required|numeric|max:1000000|min:0",
            "nodes.*" => "required|exists:nodes,id",
            "eggs.*" => "required|exists:eggs,id",
            "disabled" => "nullable",
            "billing_period" => "required|in:hourly,daily,weekly,monthly,quarterly,half-annually,annually",
        ]);

        $disabled = ! is_null($request->input('disabled'));
        $product = Product::create(array_merge($request->all(), ['disabled' => $disabled]));

        //link nodes and eggs
        $product->eggs()->attach($request->input('eggs'));
        $product->nodes()->attach($request->input('nodes'));

        return redirect()->route('admin.products.index')->with('success', __('Product has been created!'));
    }

    /**
     * Display the specified resource.
     *
     * @param  Product  $product
     * @return Application|Factory|View
     */
    public function show(Product $product)
    {
        return view('admin.products.show', [
            'product' => $product,
            'minimum_credits' => config('SETTINGS::USER:MINIMUM_REQUIRED_CREDITS_TO_MAKE_SERVER'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Product  $product
     * @return Application|Factory|View
     */
    public function edit(Product $product)
    {
        return view('admin.products.edit', [
            'product' => $product,
            'locations' => Location::with('nodes')->get(),
            'nests' => Nest::with('eggs')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return RedirectResponse
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            "name" => "required|max:30",
            "price" => "required|numeric|max:1000000|min:0",
            "memory" => "required|numeric|max:1000000|min:5",
            "cpu" => "required|numeric|max:1000000|min:0",
            "swap" => "required|numeric|max:1000000|min:0",
            "description" => "required|string|max:191",
            "disk" => "required|numeric|max:1000000|min:5",
            "io" => "required|numeric|max:1000000|min:0",
            "minimum_credits" => "required|numeric|max:1000000|min:-1",
            "databases" => "required|numeric|max:1000000|min:0",
            "backups" => "required|numeric|max:1000000|min:0",
            "allocations" => "required|numeric|max:1000000|min:0",
            "nodes.*" => "required|exists:nodes,id",
            "eggs.*" => "required|exists:eggs,id",
            "disabled" => "nullable",
            "billing_period" => "required|in:hourly,daily,weekly,monthly,quarterly,half-annually,annually",
        ]);

        $disabled = ! is_null($request->input('disabled'));
        $product->update(array_merge($request->all(), ['disabled' => $disabled]));

        //link nodes and eggs
        $product->eggs()->detach();
        $product->nodes()->detach();
        $product->eggs()->attach($request->input('eggs'));
        $product->nodes()->attach($request->input('nodes'));

        return redirect()->route('admin.products.index')->with('success', __('Product has been updated!'));
    }

    /**
     * @param  Request  $request
     * @param  Product  $product
     * @return RedirectResponse
     */
    public function disable(Request $request, Product $product)
    {
        $product->update(['disabled' => ! $product->disabled]);

        return redirect()->route('admin.products.index')->with('success', 'Product has been updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Product  $product
     * @return RedirectResponse
     */
    public function destroy(Product $product)
    {
        $servers = $product->servers()->count();
        if ($servers > 0) {
            return redirect()->back()->with('error', "Product cannot be removed while it's linked to {$servers} servers");
        }

        $product->delete();

        return redirect()->back()->with('success', __('Product has been removed!'));
    }

    /**
     * @return JsonResponse|mixed
     *
     * @throws Exception|Exception
     */
    public function dataTable()
    {
        $query = Product::with(['servers']);

        return datatables($query)
            ->addColumn('actions', function (Product $product) {
                return '
                            <a data-content="'.__('Show').'" data-toggle="popover" data-trigger="hover" data-placement="top" href="'.route('admin.products.show', $product->id).'" class="btn btn-sm text-white btn-warning mr-1"><i class="fas fa-eye"></i></a>
                            <a data-content="'.__('Clone').'" data-toggle="popover" data-trigger="hover" data-placement="top" href="'.route('admin.products.clone', $product->id).'" class="btn btn-sm text-white btn-primary mr-1"><i class="fas fa-clone"></i></a>
                            <a data-content="'.__('Edit').'" data-toggle="popover" data-trigger="hover" data-placement="top" href="'.route('admin.products.edit', $product->id).'" class="btn btn-sm btn-info mr-1"><i class="fas fa-pen"></i></a>

                           <form class="d-inline" onsubmit="return submitResult();" method="post" action="'.route('admin.products.destroy', $product->id).'">
                            '.csrf_field().'
                            '.method_field('DELETE').'
                           <button data-content="'.__('Delete').'" data-toggle="popover" data-trigger="hover" data-placement="top" class="btn btn-sm btn-danger mr-1"><i class="fas fa-trash"></i></button>
                       </form>
                ';
            })

            ->addColumn('servers', function (Product $product) {
                return $product->servers()->count();
            })
            ->addColumn('nodes', function (Product $product) {
                return $product->nodes()->count();
            })
            ->addColumn('eggs', function (Product $product) {
                return $product->eggs()->count();
            })
            ->addColumn('disabled', function (Product $product) {
                $checked = $product->disabled == false ? 'checked' : '';

                return '
                                <form class="d-inline" onsubmit="return submitResult();" method="post" action="'.route('admin.products.disable', $product->id).'">
                            '.csrf_field().'
                            '.method_field('PATCH').'
                            <div class="custom-control custom-switch">
                            <input '.$checked.' name="disabled" onchange="this.form.submit()" type="checkbox" class="custom-control-input" id="switch'.$product->id.'">
                            <label class="custom-control-label" for="switch'.$product->id.'"></label>
                          </div>
                       </form>
                ';
            })
            ->editColumn('minimum_credits', function (Product $product) {
                return $product->minimum_credits==-1 ? config('SETTINGS::USER:MINIMUM_REQUIRED_CREDITS_TO_MAKE_SERVER') : $product->minimum_credits;
            })
            ->editColumn('created_at', function (Product $product) {
                return $product->created_at ? $product->created_at->diffForHumans() : '';
            })
            ->rawColumns(['actions', 'disabled'])
            ->make();
    }
}
