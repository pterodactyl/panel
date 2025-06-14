<?php

namespace Pterodactyl\Http\Controllers\Admin\Shop;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoriesController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * CategoriesController constructor.
     * @param AlertsMessageBag $alert
     */
    public function __construct(AlertsMessageBag $alert)
    {
        $this->alert = $alert;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $categories = DB::table('game_category')->orderBy('sort', 'ASC')->get();

        return view('admin.shop.categories.index', [
            'categories' => $categories,
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|min:1|max:40',
            'short_url' => 'required|min:1|max:20',
            'image_url' => 'required|min:1',
            'hide' => 'sometimes|min:0|max:1',
        ]);

        $shortUrlExists = DB::table('game_category')->where('short_url', '=', trim($request->input('short_url')))->get();
        if (count($shortUrlExists) > 0) {
            throw new DisplayException('Short url exists.');
        }

        $lastSort = DB::table('game_category')->orderBy('sort', 'DESC')->get();

        DB::table('game_category')->insert([
            'title' => trim(strip_tags($request->input('title'))),
            'short_url' => trim($request->input('short_url')),
            'image_url' => trim($request->input('image_url')),
            'hide' => (int) $request->input('hide', 0),
            'sort' => count($lastSort) < 1 ? 1 : $lastSort[0]->sort + 1,
        ]);

        $this->alert->success('You\'ve successfully created the new category.')->flash();

        return redirect()->route('admin.shop.categories');
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Request $request, $id)
    {
        $category = DB::table('game_category')->where('id', '=', (int) $id)->get();
        if (count($category) < 1) {
            throw new NotFoundHttpException('Category not found.');
        }

        $categories = DB::table('game_category')->get();

        return view('admin.shop.categories.index', [
            'categories' => $categories,
            'currentCategory' => $category[0],
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $id)
    {
        $category = DB::table('game_category')->where('id', '=', (int) $id)->get();
        if (count($category) < 1) {
            throw new DisplayException('Category not found.');
        }

        $this->validate($request, [
            'title' => 'required|min:1|max:191',
            'short_url' => 'required|min:1|max:20',
            'image_url' => 'required|min:1',
            'hide' => 'sometimes|min:0|max:1',
        ]);

        $shortUrlExists = DB::table('game_category')->where('short_url', '=', trim($request->input('short_url')))->where('id', '!=', $category[0]->id)->get();
        if (count($shortUrlExists) > 0) {
            throw new DisplayException('Short url exists.');
        }

        DB::table('game_category')->where('id', '=', $category[0]->id)->update([
            'title' => trim(strip_tags($request->input('title'))),
            'short_url' => trim($request->input('short_url')),
            'image_url' => trim($request->input('image_url')),
            'hide' => (int) $request->input('hide', 0),
        ]);

        $this->alert->success('You\'ve successfully edited this category.')->flash();

        return redirect()->route('admin.shop.categories');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function delete(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        $category = DB::table('game_category')->where('id', '=', (int) $request->input('id'))->get();
        if (count($category) < 1) {
            throw new DisplayException('Category not found.');
        }

        $games = DB::table('games')->where('category_id', '=', $category[0]->id)->get();
        if (count($games) > 0) {
            throw new DisplayException('You can\'t delete this category because it\'s consists games.');
        }

        DB::table('game_category')->where('id', '=', $category[0]->id)->delete();

        return response()->json(['success' => true]);
    }
}
