<?php

namespace Pterodactyl\Http\Controllers\Api\Remote;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileDownloadController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    /**
     * FileDownloadController constructor.
     *
     * @param \Illuminate\Contracts\Cache\Repository $cache
     */
    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Handle a request to authenticate a download using a token and return
     * the path of the file to the daemon.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function index(Request $request): JsonResponse
    {
        $download = $this->cache->pull('Server:Downloads:' . $request->input('token', ''));

        if (is_null($download)) {
            throw new NotFoundHttpException('No file was found using the token provided.');
        }

        return response()->json([
            'path' => array_get($download, 'path'),
            'server' => array_get($download, 'server'),
        ]);
    }
}
