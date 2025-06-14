<?php

namespace Pterodactyl\Http\Controllers\Admin\Shop;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GamesController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    protected $settingsRepository;

    /**
     * CategoriesController constructor.
     * @param AlertsMessageBag $alert
     * @param SettingsRepositoryInterface $settingsRepository
     */
    public function __construct(AlertsMessageBag $alert, SettingsRepositoryInterface $settingsRepository)
    {
        $this->alert = $alert;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $categories = DB::table('game_category')->orderBy('sort', 'ASC')->get();

        return view('admin.shop.games.index', [
            'categories' => $categories,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function games(Request $request, $id)
    {
        $category = DB::table('game_category')->where('id', '=', (int) $id)->get();
        if (count($category) < 1) {
            throw new NotFoundHttpException('Category not found.');
        }

        $games = DB::table('games')->where('category_id', '=', $category[0]->id)->orderBy('sort', 'ASC')->get();

        return view('admin.shop.games.games', [
            'category' => $category[0],
            'currency' => $this->settingsRepository->get('settings::shop::currency', 'USD'),
            'games' => $games,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create(Request $request, $id)
    {
        $category = DB::table('game_category')->where('id', '=', (int) $id)->get();
        if (count($category) < 1) {
            throw new NotFoundHttpException('Category not found.');
        }

        return view('admin.shop.games.create', [
            'category' => $category[0],
            'nodes' => DB::table('nodes')->get(),
            'eggs' => DB::table('eggs')->get(),
			'currency' => $this->settingsRepository->get('settings::shop::currency', 'USD'),
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, $id)
    {
        $category = DB::table('game_category')->where('id', '=', (int) $id)->get();
        if (count($category) < 1) {
            throw new NotFoundHttpException('Category not found.');
        }

        $this->validate($request, [
            'name' => 'required|min:1|max:40',
            'image_url' => 'required|min:1',
            'short_url' => 'required|max:20',
            'price' => 'required|numeric',
            'database_limit' => 'required|integer|min:0',
            'allocation_limit' => 'required|integer|min:0',
            'backup_limit' => 'required|integer|min:0',
            'memory' => 'required|integer|min:0',
            'disk' => 'required|integer|min:0',
            'cpu' => 'required|integer|min:0',
            'swap' => 'required|integer|min:0',
            'egg_id' => 'required|integer|min:1',
            'node_ids' => 'required|array',
            'hide' => 'sometimes|min:0|max:1',
        ]);

        $shortUrlExists = DB::table('games')->where('category_id', '=', $category[0]->id)->where('short_url', '=', trim($request->input('short_url')))->get();
        if (count($shortUrlExists) > 0) {
            throw new DisplayException('Short url exists.');
        }

        $egg = DB::table('eggs')->where('id', '=', (int) $request->input('egg_id'))->get();
        if (count($egg) < 1) {
            throw new DisplayException('Egg not found.');
        }

        foreach ($request->input('node_ids', []) as $node_id) {
            $node = DB::table('nodes')->where('id', '=', $node_id)->get();
            if (count($node) < 1) {
                throw new DisplayException('Node not found.');
            }
        }

        $lastSort = DB::table('games')->where('category_id', '=', $category[0]->id)->orderBy('sort', 'DESC')->get();

        DB::table('games')->insert([
            'name' => trim(strip_tags($request->input('name'))),
            'image_url' => trim($request->input('image_url')),
            'short_url' => trim($request->input('short_url')),
            'price' => $request->input('price'),
            'database_limit' => (int) $request->input('database_limit'),
            'backup_limit' => (int) $request->input('backup_limit'),
            'allocation_limit' => (int) $request->input('allocation_limit'),
            'memory' => (int) $request->input('memory'),
            'disk' => (int) $request->input('disk'),
            'cpu' => (int) $request->input('cpu'),
            'swap' => (int) $request->input('swap'),
            'egg_id' => (int) $request->input('egg_id'),
            'node_ids' => implode(',', $request->input('node_ids')),
            'category_id' => $category[0]->id,
            'hide' => (int) $request->input('hide', 0),
            'sort' => count($lastSort) < 1 ? 1 : $lastSort[0]->sort + 1,
        ]);

        $this->alert->success('You\'ve successfully edited this game.')->flash();

        return redirect()->route('admin.shop.categories.games', $category[0]->id);
    }

    /**
     * @param Request $request
     * @param $categoryId
     * @param $gameId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Request $request, $categoryId, $gameId)
    {
        $category = DB::table('game_category')->where('id', '=', (int) $categoryId)->get();
        if (count($category) < 1) {
            throw new NotFoundHttpException('Category not found.');
        }

        $game = DB::table('games')->where('category_id', '=', $category[0]->id)->where('id', '=', (int) $gameId)->get();
        if (count($game) < 1) {
            throw new NotFoundHttpException('Game not found.');
        }

        return view('admin.shop.games.edit', [
            'category' => $category[0],
            'game' => $game[0],
            'nodes' => DB::table('nodes')->get(),
            'eggs' => DB::table('eggs')->get(),
            'currency' => $this->settingsRepository->get('settings::shop::currency', 'USD'),
            'categories' => DB::table('game_category')->where('id', '!=', $category[0]->id)->orderBy('sort', 'ASC')->get(),
        ]);
    }

    public function update(Request $request, $categoryId, $gameId)
    {
        $category = DB::table('game_category')->where('id', '=', (int) $categoryId)->get();
        if (count($category) < 1) {
            throw new NotFoundHttpException('Category not found.');
        }

        $game = DB::table('games')->where('category_id', '=', $category[0]->id)->where('id', '=', (int) $gameId)->get();
        if (count($game) < 1) {
            throw new NotFoundHttpException('Game not found.');
        }

        $this->validate($request, [
            'name' => 'required|min:1|max:40',
            'image_url' => 'required|min:1',
            'short_url' => 'required|max:20',
            'price' => 'required|numeric',
            'database_limit' => 'required|integer|min:0',
            'allocation_limit' => 'required|integer|min:0',
            'backup_limit' => 'required|integer|min:0',
            'memory' => 'required|integer|min:0',
            'disk' => 'required|integer|min:0',
            'cpu' => 'required|integer|min:0',
            'swap' => 'required|integer|min:0',
            'egg_id' => 'required|integer|min:1',
            'node_ids' => 'required|array',
            'hide' => 'sometimes|min:0|max:1',
        ]);

        $shortUrlExists = DB::table('games')->where('category_id', '=', $category[0]->id)->where('short_url', '=', trim($request->input('short_url')))->where('id', '!=', $game[0]->id)->get();
        if (count($shortUrlExists) > 0) {
            throw new DisplayException('Short url exists.');
        }

        $egg = DB::table('eggs')->where('id', '=', (int) $request->input('egg_id'))->get();
        if (count($egg) < 1) {
            throw new DisplayException('Egg not found.');
        }

        foreach ($request->input('node_ids', []) as $node_id) {
            $node = DB::table('nodes')->where('id', '=', $node_id)->get();
            if (count($node) < 1) {
                throw new DisplayException('Node not found.');
            }
        }

        DB::table('games')->where('id', '=', $game[0]->id)->update([
            'name' => trim(strip_tags($request->input('name'))),
            'image_url' => trim($request->input('image_url')),
            'short_url' => trim($request->input('short_url')),
            'price' => $request->input('price'),
            'database_limit' => (int) $request->input('database_limit'),
            'backup_limit' => (int) $request->input('backup_limit'),
            'allocation_limit' => (int) $request->input('allocation_limit'),
            'memory' => (int) $request->input('memory'),
            'disk' => (int) $request->input('disk'),
            'cpu' => (int) $request->input('cpu'),
            'swap' => (int) $request->input('swap'),
            'egg_id' => (int) $request->input('egg_id'),
            'hide' => (int) $request->input('hide', 0),
            'node_ids' => implode(',', $request->input('node_ids')),
        ]);

        $this->alert->success('You\'ve successfully created the new game.')->flash();

        return redirect()->route('admin.shop.categories.games', $category[0]->id);
    }

    /**
     * @param Request $request
     * @param $categoryId
     * @param $gameId
     * @return \Illuminate\Http\RedirectResponse
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function move(Request $request, $categoryId, $gameId)
    {
        $category = DB::table('game_category')->where('id', '=', (int) $categoryId)->get();
        if (count($category) < 1) {
            throw new NotFoundHttpException('Category not found.');
        }

        $game = DB::table('games')->where('category_id', '=', $category[0]->id)->where('id', '=', (int) $gameId)->get();
        if (count($game) < 1) {
            throw new NotFoundHttpException('Game not found.');
        }

        $this->validate($request, [
            'category_id' => 'required|integer',
        ]);

        $newCategory = DB::table('game_category')->where('id', '=', (int) $request->input('category_id'))->get();
        if (count($newCategory) < 1) {
            throw new DisplayException('New category not found.');
        }

        $lastSort = DB::table('games')->where('category_id', '=', $newCategory[0]->id)->orderBy('sort', 'DESC')->get();

        DB::table('games')->where('id', '=', $game[0]->id)->update([
            'category_id' => $newCategory[0]->id,
            'sort' => count($lastSort) < 1 ? 1 : $lastSort[0]->sort + 1,
        ]);

        $this->alert->success('You\'ve successfully created the new game.')->flash();

        return redirect()->route('admin.shop.categories.games.edit', [$newCategory[0]->id, $game[0]->id]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function delete(Request $request, $id)
    {
        $category = DB::table('game_category')->where('id', '=', (int) $id)->get();
        if (count($category) < 1) {
            throw new NotFoundHttpException('Category not found.');
        }

        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        $game = DB::table('games')->where('id', '=', (int) $request->input('id'))->where('category_id', '=', $category[0]->id)->get();
        if (count($game) < 1) {
            throw new DisplayException('Game not found.');
        }

        $servers = DB::table('servers')->where('product_id', '=', $game[0]->id)->get();
        if (count($servers) > 0) {
            throw new DisplayException('You can\'t delete this game because there it has at least 1 server.');
        }

        DB::table('games')->where('id', '=', $game[0]->id)->where('category_id', '=', $category[0]->id)->delete();

        return response()->json(['success' => true]);
    }
}
