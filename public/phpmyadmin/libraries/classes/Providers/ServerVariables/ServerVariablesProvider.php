<?php

declare(strict_types=1);

namespace PhpMyAdmin\Providers\ServerVariables;

use Williamdes\MariaDBMySQLKBS\Search;

use function class_exists;

class ServerVariablesProvider
{
    /** @var ServerVariablesProviderInterface|null */
    private static $instance = null;

    public static function getImplementation(): ServerVariablesProviderInterface
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (self::mariaDbMySqlKbsExists()) {
            self::$instance = new MariaDbMySqlKbsProvider();

            return self::$instance;
        }

        self::$instance = new VoidProvider();

        return self::$instance;
    }

    public static function mariaDbMySqlKbsExists(): bool
    {
        return class_exists(Search::class);
    }
}
