<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Integration\Api\Parity;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Api\Application\BaseTransformer;

/**
 * Renders a Fractal transformer and the API resource replacing it for the same request, the
 * way a controller does, and asserts the two responses have the same status and the same
 * bytes, and that both ran the same queries in the same order.
 *
 * Each render loads its data through the given closure, so both sides work on fresh model
 * instances and neither can see relations the other eager loaded. Each also runs under a
 * request built the same way: the same URI (so the same includes and page), and the same
 * user and API key, loaded fresh as well.
 */
final class ResourceParity
{
    private string $uri = '/';

    private ?int $user = null;

    private ?int $key = null;

    /** @var list<string> */
    private array $forced = [];

    /** @var array<string, mixed> */
    private array $meta = [];

    private bool $extensionFields = false;

    private ?int $status = null;

    /**
     * @param  class-string<BaseTransformer>  $transformer
     * @param  class-string<ApiResource<*>>  $resource
     * @param  Closure(): mixed  $data
     */
    private function __construct(
        private readonly string $transformer,
        private readonly string $resource,
        private readonly Closure $data,
        private readonly bool $list,
    ) {}

    /**
     * One model, as Fractal::item($data) renders it.
     *
     * @param  class-string<BaseTransformer>  $transformer
     * @param  class-string<ApiResource<*>>  $resource
     * @param  Closure(): mixed  $data
     */
    public static function item(string $transformer, string $resource, Closure $data): self
    {
        return new self($transformer, $resource, $data, false);
    }

    /**
     * A list or a paginator, as Fractal::collection($data) renders it.
     *
     * @param  class-string<BaseTransformer>  $transformer
     * @param  class-string<ApiResource<*>>  $resource
     * @param  Closure(): mixed  $data
     */
    public static function list(string $transformer, string $resource, Closure $data): self
    {
        return new self($transformer, $resource, $data, true);
    }

    public function request(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }

    public function actingAs(User $user, ?ApiKey $key = null): self
    {
        $this->user = $user->id;
        $this->key = $key?->id;

        return $this;
    }

    /**
     * Includes the controller always returns: parseIncludes() and withIncludes().
     */
    public function forcing(string ...$includes): self
    {
        $this->forced = [...$this->forced, ...array_values($includes)];

        return $this;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function withMeta(array $meta): self
    {
        $this->meta = [...$this->meta, ...$meta];

        return $this;
    }

    public function withExtensionFields(): self
    {
        $this->extensionFields = true;

        return $this;
    }

    /**
     * Responds through respond($status) on both sides instead of returning the payload.
     */
    public function respond(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Asserts both sides render the same response with the same queries, and returns the body.
     */
    public function assertSame(): string
    {
        $fractal = $this->render(fn (Request $request): JsonResponse => $this->fractal());
        $resource = $this->render(fn (Request $request): JsonResponse => $this->resource($request));

        expect($resource['body'])->toBe($fractal['body'])
            ->and($resource['status'])->toBe($fractal['status'])
            ->and($resource['queries'])->toBe($fractal['queries']);

        return $fractal['body'];
    }

    /**
     * The response a controller builds with Fractal.
     */
    private function fractal(): JsonResponse
    {
        $transformer = App::make($this->transformer);
        if ($this->extensionFields) {
            $transformer->withExtensionFields();
        }

        $data = ($this->data)();
        $fractal = ($this->list ? Fractal::collection($data) : Fractal::item($data))->transformWith($transformer);
        if ($this->forced !== []) {
            $fractal->parseIncludes($this->forced);
        }

        if ($this->meta !== []) {
            $fractal->addMeta($this->meta);
        }

        // A controller returns the payload array and the router turns it into a JsonResponse.
        return $this->status === null ? new JsonResponse($fractal->toResponseArray()) : $fractal->respond($this->status);
    }

    /**
     * The response a controller builds with the resource.
     */
    private function resource(Request $request): JsonResponse
    {
        $data = ($this->data)();
        $resource = $this->list ? $this->resource::collection($data) : new $this->resource($data);
        if ($this->extensionFields && $resource instanceof ApiResource) {
            $resource->withExtensionFields();
        }

        if ($this->forced !== []) {
            $resource->withIncludes(...$this->forced);
        }

        if ($this->meta !== []) {
            $resource->withMeta($this->meta);
        }

        return $this->status === null ? $resource->toResponse($request) : $resource->respond($this->status);
    }

    /**
     * @param  Closure(Request): JsonResponse  $respond
     * @return array{status: int, body: string, queries: list<array{0: string, 1: array<array-key, mixed>}>}
     */
    private function render(Closure $respond): array
    {
        // Bound as the HTTP kernel binds a request, since transformers read it through the
        // Request facade. Binding resets the user resolver, so the user is set afterwards.
        $request = Request::create($this->uri, server: ['HTTP_ACCEPT' => 'application/json']);
        App::instance('request', $request);
        App::instance(Request::class, $request);
        Facade::clearResolvedInstance('request');

        if ($this->user !== null) {
            $user = User::query()->findOrFail($this->user);
            if ($this->key !== null) {
                $user->withAccessToken(ApiKey::query()->findOrFail($this->key));
            }

            $request->setUserResolver(fn (): User => $user);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $response = $respond($request);
        } finally {
            DB::disableQueryLog();
        }

        return [
            'status' => $response->getStatusCode(),
            'body' => (string) $response->getContent(),
            'queries' => array_map(fn (array $query): array => [$query['query'], $query['bindings']], DB::getQueryLog()),
        ];
    }
}
