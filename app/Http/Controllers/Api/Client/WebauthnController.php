<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\WebauthnKey;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\PublicKeyCredentialCreationOptions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Transformers\Api\Client\WebauthnKeyTransformer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WebauthnController extends ClientApiController
{
    private const SESSION_PUBLICKEY_CREATION = 'webauthn.publicKeyCreation';

    /**
     * ?
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(Request $request): array
    {
        return $this->fractal->collection(WebauthnKey::query()->where('user_id', '=', $request->user()->id)->get())
            ->transformWith(WebauthnKeyTransformer::class)
            ->toArray();
    }

    /**
     * ?
     */
    public function register(Request $request): JsonResponse
    {
        if (!Webauthn::canRegister($request->user())) {
            return new JsonResponse([
                'error' => [
                    'message' => trans('webauthn::errors.cannot_register_new_key'),
                ],
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $publicKey = Webauthn::getRegisterData($request->user());

        $request->session()->put(self::SESSION_PUBLICKEY_CREATION, $publicKey);
        $request->session()->save();

        return new JsonResponse([
            'public_key' => $publicKey,
        ]);
    }

    /**
     * ?
     *
     * @return array|JsonResponse
     */
    public function create(Request $request)
    {
        if (!Webauthn::canRegister($request->user())) {
            return new JsonResponse([
                'error' => [
                    'message' => trans('webauthn::errors.cannot_register_new_key'),
                ],
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        if ($request->input('register') === null) {
            throw new BadRequestHttpException('Missing register data in request body.');
        }

        if ($request->input('name') === null) {
            throw new BadRequestHttpException('Missing name in request body.');
        }

        try {
            $publicKey = $request->session()->pull(self::SESSION_PUBLICKEY_CREATION);
            if (!$publicKey instanceof PublicKeyCredentialCreationOptions) {
                throw new ModelNotFoundException(trans('webauthn::errors.create_data_not_found'));
            }

            $webauthnKey = Webauthn::doRegister(
                $request->user(),
                $publicKey,
                $request->input('register'),
                $request->input('name'),
            );



            return $this->fractal->item($webauthnKey)
                ->transformWith(WebauthnKeyTransformer::class)
                ->toArray();
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ], JsonResponse::HTTP_FORBIDDEN);
        }
    }

    /**
     * ?
     */
    public function deleteKey(Request $request, int $webauthnKeyId): JsonResponse
    {
        try {
            WebauthnKey::query()
                ->where('user_id', $request->user()->getAuthIdentifier())
                ->findOrFail($webauthnKeyId)
                ->delete();

            return new JsonResponse([
                'deleted' => true,
                'id' => $webauthnKeyId,
            ]);
        } catch (ModelNotFoundException $e) {
            return new JsonResponse([
                'error' => [
                    'message' => trans('webauthn::errors.object_not_found'),
                ],
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
