<?php

declare(strict_types=1);

namespace PhpMyAdmin\Providers\ServerVariables;

use Williamdes\MariaDBMySQLKBS\KBException;
use Williamdes\MariaDBMySQLKBS\Search as KBSearch;

class MariaDbMySqlKbsProvider implements ServerVariablesProviderInterface
{
    public function getVariableType(string $name): ?string
    {
        try {
            return KBSearch::getVariableType($name);
        } catch (KBException $e) {
            return null;
        }
    }

    public function getStaticVariables(): array
    {
        return [];
    }

    public function getDocLinkByNameMariaDb(string $name): ?string
    {
        try {
            return KBSearch::getByName($name, KBSearch::MARIADB);
        } catch (KBException $e) {
            return null;
        }
    }

    public function getDocLinkByNameMysql(string $name): ?string
    {
        try {
            return KBSearch::getByName($name, KBSearch::MYSQL);
        } catch (KBException $e) {
            return null;
        }
    }
}
