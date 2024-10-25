<?php

declare(strict_types=1);

namespace PhpMyAdmin\Providers\ServerVariables;

class VoidProvider implements ServerVariablesProviderInterface
{
    public function getVariableType(string $name): ?string
    {
        return null;
    }

    public function getStaticVariables(): array
    {
        return [];
    }

    public function getDocLinkByNameMariaDb(string $name): ?string
    {
        return null;
    }

    public function getDocLinkByNameMysql(string $name): ?string
    {
        return null;
    }
}
