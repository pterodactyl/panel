<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request as RequestFacade;
use League\Fractal\Resource\Collection as ResourceCollection;
use League\Fractal\Scope;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use UnexpectedValueException;

/**
 * @method array<string, mixed> transform(Model $model)
 */
abstract class BaseTransformer extends TransformerAbstract
{
    public const string RESPONSE_TIMEZONE = 'UTC';

    /** @var list<string> */
    protected array $eagerLoads = [];

    /** @var list<string> */
    protected array $eagerLoadCounts = [];

    /** @var array<string, string> relation alias => column */
    protected array $eagerLoadSums = [];

    /** @var array<string, array{relation: string, transformer?: class-string<BaseTransformer>, ability?: string}> */
    protected array $includeRelations = [];

    private ?Request $request = null;

    /**
     * Return the resource name for the JSONAPI output.
     */
    abstract public function getResourceName(): string;

    /**
     * Batch dependencies across the entire resource before Fractal visits individual models.
     * Only declared includes are loaded; serialization retains its own authorization checks.
     *
     * @param  ModelCollection<int, Model>  $models
     */
    public function prepareCollection(ModelCollection $models, Scope $scope): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $models->loadMissing($this->eagerLoads);
        foreach ($this->eagerLoadCounts as $relation) {
            $models->reject(fn (Model $model): bool => array_key_exists($relation.'_count', $model->getAttributes()))->loadCount($relation);
        }

        foreach ($this->eagerLoadSums as $relation => $column) {
            $alias = explode(' as ', $relation)[1];
            $models->reject(fn (Model $model): bool => array_key_exists($alias, $model->getAttributes()))->loadSum($relation, $column);
        }

        foreach ($this->includeRelations as $include => $definition) {
            if ((! in_array($include, $this->getDefaultIncludes(), true) && ! $scope->isRequested($include)) || $scope->isExcluded($include)) {
                continue;
            }

            if (isset($definition['ability']) && ! $this->authorize($definition['ability'])) {
                continue;
            }

            $models->loadMissing($definition['relation']);
            if (! isset($definition['transformer'])) {
                continue;
            }

            // A relation value is a Model, a Collection of Models, or null; flattening one
            // level and keeping only Models covers all three without a runtime type check.
            $related = new ModelCollection(
                $models->pluck($definition['relation'])->flatten(1)->whereInstanceOf(Model::class)->values()->all(),
            );

            $transformer = $this->makeTransformer($definition['transformer']);
            $childScope = $scope->embedChildScope($include, new ResourceCollection($related, $transformer));
            $transformer->prepareCollection($related, $childScope);
        }
    }

    /**
     * Sets the request on the instance.
     */
    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    /**
     * The request this transformer serializes for; the current request unless one was set.
     */
    protected function request(): Request
    {
        return $this->request ??= RequestFacade::instance();
    }

    /**
     * Determine if the API key loaded onto the transformer has permission
     * to access a different resource. This is used when including other
     * models on a transformation request.
     *
     * @deprecated - prefer $user->can/cannot methods
     */
    protected function authorize(string $resource): bool
    {
        $allowed = [ApiKey::TYPE_ACCOUNT, ApiKey::TYPE_APPLICATION];

        $token = $this->request()->user()?->currentAccessToken();
        if (! $token instanceof ApiKey || ! in_array($token->key_type, $allowed)) {
            return false;
        }

        // If this is not a deprecated application token type we can only check that
        // the user is a root admin at the moment. In a future release we'll be rolling
        // out more specific permissions for keys.
        if ($token->key_type === ApiKey::TYPE_ACCOUNT) {
            return $this->request()->user()->root_admin;
        }

        return AdminAcl::check($token, $resource);
    }

    /**
     * Create a new instance of the transformer and pass along the currently
     * set API key.
     *
     * @template T of \Pterodactyl\Transformers\Api\Application\BaseTransformer
     *
     * @param  class-string<T>  $abstract
     * @return T
     *
     * @throws InvalidTransformerLevelException
     *
     * @noinspection PhpDocSignatureInspection
     */
    protected function makeTransformer(string $abstract): self
    {
        throw_unless(is_subclass_of($abstract, self::class), InvalidTransformerLevelException::class, "Transformer [$abstract] must extend ".self::class.'.');

        $transformer = App::make($abstract);
        throw_unless($transformer instanceof $abstract, UnexpectedValueException::class, "The container did not resolve transformer [$abstract].");

        return $transformer->setRequest($this->request());
    }

    /**
     * Return an ISO-8601 formatted timestamp to use in the API response.
     */
    protected function formatTimestamp(CarbonInterface|string|null $timestamp): string
    {
        throw_if($timestamp === null, UnexpectedValueException::class, 'Cannot format a missing model timestamp.');

        $value = $timestamp instanceof CarbonInterface
            ? CarbonImmutable::instance($timestamp)
            : CarbonImmutable::createFromFormat(CarbonInterface::DEFAULT_TO_STRING_FORMAT, $timestamp);

        throw_unless($value instanceof CarbonImmutable, UnexpectedValueException::class, "The model timestamp does not match Laravel's storage format.");

        return $value->setTimezone(self::RESPONSE_TIMEZONE)->toAtomString();
    }
}
