<?php

namespace Pterodactyl\Transformers\Api;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Webmozart\Assert\Assert;
use League\Fractal\Resource\Item;
use Illuminate\Container\Container;
use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;

/**
 * @method array transform(\Pterodactyl\Models\Model $model)
 */
abstract class Transformer extends TransformerAbstract
{
    protected static string $timezone = 'UTC';

    protected Request $request;

    /**
     * Sets the request instance onto the transformer abstract from the container. This
     * will also automatically handle dependency injection for the class implementing
     * this abstract.
     */
    public function __construct()
    {
        $this->request = Container::getInstance()->make('request');

        if (method_exists($this, 'handle')) {
            Container::getInstance()->call([$this, 'handle']);
        }
    }

    /**
     * Returns the resource name for the transformed item.
     */
    abstract public function getResourceName(): string;

    /**
     * Returns the authorized user for the request.
     */
    protected function user(): User
    {
        return $this->request->user();
    }

    /**
     * Determines if the user making this request is authorized to access the given
     * resource on the API. This is used when requested included items to ensure that
     * the user and key are authorized to see the result.
     *
     * TODO: implement this with the new API key formats.
     */
    protected function authorize(string $resource): bool
    {
        return $this->request->user() instanceof User;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $data
     * @param callable|\League\Fractal\TransformerAbstract $transformer
     */
    protected function item($data, $transformer, ?string $resourceKey = null): Item
    {
        if (!$transformer instanceof \Closure) {
            self::assertSameNamespace($transformer);
        }

        $item = parent::item($data, $transformer, $resourceKey);

        if (!$item->getResourceKey() && method_exists($transformer, 'getResourceName')) {
            $item->setResourceKey($transformer->getResourceName());
        }

        return $item;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $data
     * @param callable|\League\Fractal\TransformerAbstract $transformer
     */
    protected function collection($data, $transformer, ?string $resourceKey = null): Collection
    {
        if (!$transformer instanceof \Closure) {
            self::assertSameNamespace($transformer);
        }

        $collection = parent::collection($data, $transformer, $resourceKey);

        if (!$collection->getResourceKey() && method_exists($transformer, 'getResourceName')) {
            $collection->setResourceKey($transformer->getResourceName());
        }

        return $collection;
    }

    /**
     * Sets the default timezone to use for transformed responses. Pass a null value
     * to return back to the default timezone (UTC).
     */
    public static function setTimezone(string $tz = null)
    {
        static::$timezone = $tz ?? 'UTC';
    }

    /**
     * Asserts that the given transformer is the same base namespace as the class that
     * implements this abstract transformer class. This prevents a client or application
     * transformer from unintentionally transforming a resource using an unexpected type.
     *
     * @param callable|\League\Fractal\TransformerAbstract $transformer
     */
    protected static function assertSameNamespace($transformer)
    {
        Assert::subclassOf($transformer, TransformerAbstract::class);

        $namespace = substr(get_class($transformer), 0, strlen(class_basename($transformer)) * -1);
        $expected = substr(static::class, 0, strlen(class_basename(static::class)) * -1);

        Assert::same($namespace, $expected, 'Cannot invoke a new transformer (%s) that is not in the same namespace (%s).');
    }

    /**
     * Returns an ISO-8601 formatted timestamp to use in API responses. This
     * time is returned in the default transformer timezone if no timezone value
     * is provided.
     *
     * If no time is provided a null value is returned.
     *
     * @param string|\DateTimeInterface|null $timestamp
     */
    protected static function formatTimestamp($timestamp, string $tz = null): ?string
    {
        if (empty($timestamp)) {
            return null;
        }

        if ($timestamp instanceof \DateTimeInterface) {
            $value = CarbonImmutable::instance($timestamp);
        } else {
            $value = CarbonImmutable::createFromFormat(CarbonInterface::DEFAULT_TO_STRING_FORMAT, $timestamp);
        }

        return $value->setTimezone($tz ?? self::$timezone)->toAtomString();
    }
}
