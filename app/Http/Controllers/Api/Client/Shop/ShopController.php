<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Shop;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Http\Requests\Api\Client\ShopRequest;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;

class ShopController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\Servers\ServerCreationService
     */
    protected $serverCreationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    protected $settingsRepository;

    /**
     * ShopController constructor.
     * @param ServerCreationService $serverCreationService
     * @param SettingsRepositoryInterface $settingsRepository
     */
    public function __construct(ServerCreationService $serverCreationService, SettingsRepositoryInterface $settingsRepository)
    {
        parent::__construct();

        $this->serverCreationService = $serverCreationService;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @param ShopRequest $request
     * @return array
     */
    public function categories(ShopRequest $request)
    {
        return [
            'success' => true,
            'data' => [
                'categories' => $categories = DB::table('game_category')->where('hide', '=', 0)->orderBy('sort', 'ASC')->get(),
            ],
        ];
    }

    /**
     * @param ShopRequest $request
     * @param $shortUrl
     * @return array
     * @throws DisplayException
     */
    public function games(ShopRequest $request, $shortUrl)
    {
        $category = DB::table('game_category')->where('hide', '=', 0)->where('short_url', '=', trim(strip_tags($shortUrl)))->get();
        if (count($category) < 1) {
            throw new DisplayException('Server Category not found. Please refresh or contact support.');
        }

        return [
            'success' => true,
            'data' => [
                'category' => $category[0],
                'games' => DB::table('games')->where('category_id', '=', $category[0]->id)->where('hide', '=', 0)->orderBy('sort', 'ASC')->get(),
                'balance' => Auth::user()->credit,
                'tos' => [
                    'tos_url' => $this->settingsRepository->get('settings::shop::tos_url', ''),
                    'tos' => $this->settingsRepository->get('settings::shop::tos', ''),
                ],
                'currency' => $this->settingsRepository->get('settings::shop::currency', 'USD'),
            ],
        ];
    }

    /**
     * @param ShopRequest $request
     * @return array
     * @throws DisplayException
     */
    public function order(ShopRequest $request)
    {
        $game = DB::table('games')->where('id', '=', (int) $request->input('gameId', 0))->where('hide', '=', 0)->get();
        if (count($game) < 1) {
            throw new DisplayException('The requested game could not be found. Please refresh or contact support.');
        }

        if (Auth::user()->credit < $game[0]->price) {
            throw new DisplayException('Your current balance is insufficient to renew this server.');
        }

        $node_ids = explode(',', $game[0]->node_ids);
        $node = DB::table('nodes')->where('id', '=', $node_ids[array_rand($node_ids)])->get();
        if (count($node) < 1) {
            throw new DisplayException('All of our servers are currently at full capacity. Please try again later.');
        }

        $allocations = json_decode(json_encode(DB::table('allocations')->where('node_id', '=', $node[0]->id)->whereNull('server_id')->get()), true);
        if (count($allocations) < 1) {
            throw new DisplayException('No IP/port allocations are currently available.');
        }

        $allocation = $allocations[array_rand($allocations)];

        $egg = DB::table('eggs')->where('id', '=', $game[0]->egg_id)->get();
        if (count($egg) < 1) {
            throw new DisplayException('The requested game configuration could not be found. Please refresh or contact support.');
        }

        $env = DB::table('egg_variables')->where('egg_id', '=', $egg[0]->id)->get();
        $environment = [];

        foreach ($env as $item) {
            $environment[$item->env_variable] = $item->default_value;
        }

        try {
            $newServer = $this->serverCreationService->handle([
                'name' => sprintf('%s %s\'s server', Auth::user()->name_first, Auth::user()->name_last),
                'description' => '',
                'owner_id' => Auth::user()->id,
                'node_id' => $node[0]->id,
                'allocation_id' => $allocation['id'],
                'allocation_additional' => [],
                'database_limit' => $game[0]->database_limit,
                'allocation_limit' => $game[0]->allocation_limit,
                'backup_limit' => $game[0]->backup_limit,
                'memory' => $game[0]->memory,
                'disk' => $game[0]->disk,
                'swap' => $game[0]->swap,
                'io' => 500,
                'cpu' => $game[0]->cpu,
                'threads' => '',
                'nest_id' => $egg[0]->nest_id,
                'egg_id' => $egg[0]->id,
                'pack_id' => 0,
                'startup' => $egg[0]->startup,
                'image' => json_decode($egg[0]->docker_images, true)[array_keys(json_decode($egg[0]->docker_images, true))[0]],
                'environment' => $environment,
                'start_on_completion' => true,
            ]);
        } catch (ValidationException $e) {
            throw new DisplayException('Failed to deploy the server. If the issue persists, please contact support.');
        } catch (NoViableAllocationException $e) {
            throw new DisplayException('Failed to deploy the server. If the issue persists, please contact support.');
        } catch (NoViableNodeException $e) {
            throw new DisplayException('Failed to deploy the server. If the issue persists, please contact support.');
        } catch (DisplayException $e) {
            throw new DisplayException('Failed to deploy the server. If the issue persists, please contact support.');
        } catch (RecordNotFoundException $e) {
            throw new DisplayException('Failed to deploy the server. If the issue persists, please contact support.');
        } catch (\Throwable $e) {
            throw new DisplayException('Failed to deploy the server. If the issue persists, please contact support.');
        }

        DB::table('servers')->where('id', '=', $newServer->id)->update([
            'product_id' => $game[0]->id,
            'expired_at' => Carbon::now()->addDays(30),
        ]);

        DB::table('users')->where('id', '=', Auth::user()->id)->update([
            'credit' => Auth::user()->credit - $game[0]->price,
        ]);

        return [
            'success' => true,
            'data' => [],
        ];
    }
}
