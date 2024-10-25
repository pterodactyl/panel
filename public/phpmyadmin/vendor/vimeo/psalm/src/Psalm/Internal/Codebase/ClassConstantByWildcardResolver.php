<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Codebase;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TMixed;

use function array_merge;
use function array_values;
use function preg_match;
use function sprintf;
use function str_replace;

/**
 * @internal
 */
final class ClassConstantByWildcardResolver
{
    /**
     * @var Codebase
     */
    private $codebase;

    public function __construct(Codebase $codebase)
    {
        $this->codebase = $codebase;
    }

    /**
     * @return list<Atomic>|null
     */
    public function resolve(string $class_name, string $constant_pattern): ?array
    {
        if (!$this->codebase->classlike_storage_provider->has($class_name)) {
            return null;
        }

        $constant_regex_pattern = sprintf('#^%s$#', str_replace('*', '.*', $constant_pattern));

        $class_like_storage = $this->codebase->classlike_storage_provider->get($class_name);
        $matched_class_constant_types = [];

        foreach ($class_like_storage->constants as $constant => $class_constant_storage) {
            if (preg_match($constant_regex_pattern, $constant) === 0) {
                continue;
            }

            if (! $class_constant_storage->type) {
                $matched_class_constant_types[] = [new TMixed()];
                continue;
            }

            $matched_class_constant_types[] = $class_constant_storage->type->getAtomicTypes();
        }

        return array_values(array_merge([], ...$matched_class_constant_types));
    }
}
