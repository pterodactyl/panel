<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\API\Admin;

use Log;
use Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\oldUserRepository;
use Pterodactyl\Transformers\Admin\UserTransformer;
use Pterodactyl\Exceptions\DisplayValidationException;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class UserController extends Controller
{
    /**
     * Controller to handle returning all users on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $this->authorize('user-list', $request->apiKey());

        $users = User::paginate(config('pterodactyl.paginate.api.users'));
        $fractal = Fractal::create()->collection($users)
            ->transformWith(new UserTransformer($request))
            ->withResourceName('user')
            ->paginateWith(new IlluminatePaginatorAdapter($users));

        if (config('pterodactyl.api.include_on_list') && $request->input('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->toArray();
    }

    /**
     * Display information about a single user on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return array
     */
    public function view(Request $request, $id)
    {
        $this->authorize('user-view', $request->apiKey());

        $fractal = Fractal::create()->item(User::findOrFail($id));
        if ($request->input('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->transformWith(new UserTransformer($request))
            ->withResourceName('user')
            ->toArray();
    }

    /**
     * Create a new user on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|array
     */
    public function store(Request $request)
    {
        $this->authorize('user-create', $request->apiKey());

        $repo = new oldUserRepository;
        try {
            $user = $repo->create($request->only([
                'custom_id', 'email', 'password', 'name_first',
                'name_last', 'username', 'root_admin',
            ]));

            $fractal = Fractal::create()->item($user)->transformWith(new UserTransformer($request));
            if ($request->input('include')) {
                $fractal->parseIncludes(explode(',', $request->input('include')));
            }

            return $fractal->withResourceName('user')->toArray();
        } catch (DisplayValidationException $ex) {
            return response()->json([
                'error' => json_decode($ex->getMessage()),
            ], 400);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to create this user. Please try again.',
            ], 500);
        }
    }

    /**
     * Update a user.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $user)
    {
        $this->authorize('user-edit', $request->apiKey());

        $repo = new oldUserRepository;
        try {
            $user = $repo->update($user, $request->intersect([
                'email', 'password', 'name_first',
                'name_last', 'username', 'root_admin',
            ]));

            $fractal = Fractal::create()->item($user)->transformWith(new UserTransformer($request));
            if ($request->input('include')) {
                $fractal->parseIncludes(explode(',', $request->input('include')));
            }

            return $fractal->withResourceName('user')->toArray();
        } catch (DisplayValidationException $ex) {
            return response()->json([
                'error' => json_decode($ex->getMessage()),
            ], 400);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to update this user. Please try again.',
            ], 500);
        }
    }

    /**
     * Delete a user from the system.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id)
    {
        $this->authorize('user-delete', $request->apiKey());

        $repo = new oldUserRepository;
        try {
            $repo->delete($id);

            return response('', 204);
        } catch (DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 400);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to delete this user. Please try again.',
            ], 500);
        }
    }
}
