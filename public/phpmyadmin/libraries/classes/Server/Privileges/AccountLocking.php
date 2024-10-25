<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\Privileges;

use Exception;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Compatibility;

use function __;
use function sprintf;

final class AccountLocking
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    /**
     * @throws Exception
     */
    public function lock(string $user, string $host): void
    {
        if (! Compatibility::hasAccountLocking($this->dbi->isMariaDB(), $this->dbi->getVersion())) {
            throw new Exception(__('Account locking is not supported.'));
        }

        $statement = sprintf(
            'ALTER USER \'%s\'@\'%s\' ACCOUNT LOCK;',
            $this->dbi->escapeString($user),
            $this->dbi->escapeString($host)
        );
        if ($this->dbi->tryQuery($statement) !== false) {
            return;
        }

        throw new Exception($this->dbi->getError());
    }

    /**
     * @throws Exception
     */
    public function unlock(string $user, string $host): void
    {
        if (! Compatibility::hasAccountLocking($this->dbi->isMariaDB(), $this->dbi->getVersion())) {
            throw new Exception(__('Account locking is not supported.'));
        }

        $statement = sprintf(
            'ALTER USER \'%s\'@\'%s\' ACCOUNT UNLOCK;',
            $this->dbi->escapeString($user),
            $this->dbi->escapeString($host)
        );
        if ($this->dbi->tryQuery($statement) !== false) {
            return;
        }

        throw new Exception($this->dbi->getError());
    }
}
