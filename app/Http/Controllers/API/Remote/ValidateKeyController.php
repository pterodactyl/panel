<?php
/*
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

namespace Pterodactyl\Http\Controllers\Api\Remote;

use Spatie\Fractal\Fractal;
use Illuminate\Http\Response;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\HttpException;
use League\Fractal\Serializer\JsonApiSerializer;
use Pterodactyl\Transformers\Daemon\ApiKeyTransformer;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class ValidateKeyController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface
     */
    protected $daemonKeyRepository;

    /**
     * @var \Spatie\Fractal\Fractal
     */
    protected $fractal;

    /**
     * ValidateKeyController constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application                   $app
     * @param \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface $daemonKeyRepository
     * @param \Spatie\Fractal\Fractal                                        $fractal
     */
    public function __construct(
        Application $app,
        DaemonKeyRepositoryInterface $daemonKeyRepository,
        Fractal $fractal
    ) {
        $this->app = $app;
        $this->daemonKeyRepository = $daemonKeyRepository;
        $this->fractal = $fractal;
    }

    /**
     * Return the server(s) and permissions associated with an API key.
     *
     * @param string $token
     * @return array
     *
     * @throws \Illuminate\Foundation\Testing\HttpException
     */
    public function index($token)
    {
        if (! starts_with($token, DaemonKeyRepositoryInterface::INTERNAL_KEY_IDENTIFIER)) {
            throw new HttpException(Response::HTTP_NOT_IMPLEMENTED);
        }

        try {
            $key = $this->daemonKeyRepository->getKeyWithServer($token);
        } catch (RecordNotFoundException $exception) {
            throw new NotFoundHttpException;
        }

        if ($key->getRelation('server')->suspended || $key->getRelation('server')->installed !== 1) {
            throw new NotFoundHttpException;
        }

        return $this->fractal->item($key, $this->app->make(ApiKeyTransformer::class), 'server')
            ->serializeWith(JsonApiSerializer::class)
            ->toArray();
    }
}
