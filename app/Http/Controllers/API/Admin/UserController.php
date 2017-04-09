<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Http\Controllers\API\Admin;

use Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\UserRepository;
use Pterodactyl\Transformers\Admin\UserTransformer;
use Pterodactyl\Exceptions\DisplayValidationException;

class UserController extends Controller
{
    /**
     * Controller to handle returning all users on the system.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function index(Request $request)
    {
        $this->authorize('user-list', $request->apiKey());

        $fractal = Fractal::create()->collection(User::all());
        if ($request->input('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->transformWith(new UserTransformer($request))
            ->withResourceName('user')
            ->toArray();
    }

    /**
     * Display information about a single user on the system.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|array
     */
    public function store(Request $request)
    {
        $this->authorize('user-create', $request->apiKey());

        $repo = new UserRepository;
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $user)
    {
        $this->authorize('user-edit', $request->apiKey());

        $repo = new UserRepository;
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id)
    {
        $this->authorize('user-delete', $request->apiKey());

        $repo = new UserRepository;
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
