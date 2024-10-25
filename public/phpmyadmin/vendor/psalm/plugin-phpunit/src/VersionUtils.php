<?php

namespace Psalm\PhpUnitPlugin;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use PackageVersions\Versions;

abstract class VersionUtils
{
    /** @param "!="|"<"|"<="|"<>"|"="|"=="|">"|">=" $op */
    public static function packageVersionIs(string $package, string $op, string $ref): bool
    {
        try {
            /**
             * @psalm-suppress RedundantCondition
             * @psalm-suppress DeprecatedClass
             * @psalm-suppress ArgumentTypeCoercion
             * @psalm-suppress RedundantCast
             * @psalm-suppress RedundantCondition
             */
            $currentVersion = (string) explode('@', Versions::getVersion($package))[0];
        } catch (\OutOfBoundsException $exception) {
            return false;
        }

        $parser = new VersionParser();

        $currentVersion = $parser->normalize($currentVersion);
        $ref = $parser->normalize($ref);

        return Comparator::compare($currentVersion, $op, $ref);
    }
}
