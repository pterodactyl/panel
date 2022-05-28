<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\AccountLog;
use Pterodactyl\Transformers\Api\Client\AccountLogTransformer;

class AccountLogController extends ClientApiController
{
    /**
     * AccountLogController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all account logs a user owns and return them.
     */
    public function index(Request $request): array
    {
        return $this->fractal->collection($request->user()->account_logs)
            ->transformWith($this->getTransformer(AccountLogTransformer::class))
            ->toArray();
    }

    /**
     * Delete all account logs a user owns.
     */
    public function delete(Request $request, AccountLog $logs)
    {
        $logs->where('user_id', $request->user()->id)->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}