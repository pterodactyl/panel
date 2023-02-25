<?php

namespace App\Http\Controllers\Admin;

use App\Models\ShopProduct;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class ShopProductController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|Response
     */
    public function index(Request $request)
    {
        $isPaymentSetup = false;

        if (
            env('APP_ENV') == 'local' ||
            config('SETTINGS::PAYMENTS:PAYPAL:SECRET') && config('SETTINGS::PAYMENTS:PAYPAL:CLIENT_ID') ||
            config('SETTINGS::PAYMENTS:STRIPE:SECRET') && config('SETTINGS::PAYMENTS:STRIPE:ENDPOINT_SECRET') && config('SETTINGS::PAYMENTS:STRIPE:METHODS')
        ) {
            $isPaymentSetup = true;
        }

        return view('admin.store.index', [
            'isPaymentSetup' => $isPaymentSetup,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View|Response
     */
    public function create()
    {
        return view('admin.store.create', [
            'currencyCodes' => config('currency_codes'),
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
            'disabled' => 'nullable',
            'type' => 'required|string',
            'currency_code' => ['required', 'string', 'max:3', Rule::in(config('currency_codes'))],
            'price' => "required|regex:/^\d+(\.\d{1,2})?$/",
            'quantity' => 'required|numeric',
            'description' => 'required|string|max:60',
            'display' => 'required|string|max:60',
        ]);

        $disabled = !is_null($request->input('disabled'));
        ShopProduct::create(array_merge($request->all(), ['disabled' => $disabled]));

        return redirect()->route('admin.store.index')->with('success', __('Store item has been created!'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  ShopProduct  $shopProduct
     * @return Application|Factory|View|Response
     */
    public function edit(ShopProduct $shopProduct)
    {
        return view('admin.store.edit', [
            'currencyCodes' => config('currency_codes'),
            'shopProduct' => $shopProduct,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  ShopProduct  $shopProduct
     * @return RedirectResponse
     */
    public function update(Request $request, ShopProduct $shopProduct)
    {
        $request->validate([
            'disabled' => 'nullable',
            'type' => 'required|string',
            'currency_code' => ['required', 'string', 'max:3', Rule::in(config('currency_codes'))],
            'price' => "required|regex:/^\d+(\.\d{1,2})?$/",
            'quantity' => 'required|numeric|max:100000000',
            'description' => 'required|string|max:60',
            'display' => 'required|string|max:60',
        ]);

        $disabled = !is_null($request->input('disabled'));
        $shopProduct->update(array_merge($request->all(), ['disabled' => $disabled]));

        return redirect()->route('admin.store.index')->with('success', __('Store item has been updated!'));
    }

    /**
     * @param  Request  $request
     * @param  ShopProduct  $shopProduct
     * @return RedirectResponse
     */
    public function disable(Request $request, ShopProduct $shopProduct)
    {
        $shopProduct->update(['disabled' => !$shopProduct->disabled]);

        return redirect()->route('admin.store.index')->with('success', __('Product has been updated!'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  ShopProduct  $shopProduct
     * @return RedirectResponse
     */
    public function destroy(ShopProduct $shopProduct)
    {
        $shopProduct->delete();

        return redirect()->back()->with('success', __('Store item has been removed!'));
    }

    public function dataTable(Request $request)
    {
        $query = ShopProduct::query();


        return datatables($query)
            ->addColumn('actions', function (ShopProduct $shopProduct) {
                return '
                            <a data-content="' . __('Edit') . '" data-toggle="popover" data-trigger="hover" data-placement="top" href="' . route('admin.store.edit', $shopProduct->id) . '" class="btn btn-sm btn-info mr-1"><i class="fas fa-pen"></i></a>

                           <form class="d-inline" onsubmit="return submitResult();" method="post" action="' . route('admin.store.destroy', $shopProduct->id) . '">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                           <button data-content="' . __('Delete') . '" data-toggle="popover" data-trigger="hover" data-placement="top" class="btn btn-sm btn-danger mr-1"><i class="fas fa-trash"></i></button>
                       </form>
                ';
            })
            ->addColumn('disabled', function (ShopProduct $shopProduct) {
                $checked = $shopProduct->disabled == false ? 'checked' : '';

                return '
                                <form class="d-inline" onsubmit="return submitResult();" method="post" action="' . route('admin.store.disable', $shopProduct->id) . '">
                            ' . csrf_field() . '
                            ' . method_field('PATCH') . '
                            <div class="custom-control custom-switch">
                            <input ' . $checked . ' name="disabled" onchange="this.form.submit()" type="checkbox" class="custom-control-input" id="switch' . $shopProduct->id . '">
                            <label class="custom-control-label" for="switch' . $shopProduct->id . '"></label>
                          </div>
                       </form>
                ';
            })
            ->editColumn('created_at', function (ShopProduct $shopProduct) {
                return $shopProduct->created_at ? $shopProduct->created_at->diffForHumans() : '';
            })
            ->editColumn('price', function (ShopProduct $shopProduct) {
                return $shopProduct->formatToCurrency($shopProduct->price);
            })
            ->rawColumns(['actions', 'disabled'])
            ->make();
    }
}
