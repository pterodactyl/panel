<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

/**
 * @psalm-immutable
 */
final class Debug
{
    /**
     * Output executed queries and their execution times.
     *
     * @var bool
     */
    public $sql;

    /**
     * Log executed queries and their execution times to syslog.
     *
     * @var bool
     */
    public $sqllog;

    /**
     * Enable to let server present itself as demo server.
     *
     * @var bool
     */
    public $demo;

    /**
     * Enable Simple two-factor authentication.
     *
     * @var bool
     */
    public $simple2fa;

    /**
     * @param mixed[] $debug
     */
    public function __construct(array $debug = [])
    {
        $this->sql = $this->setSql($debug);
        $this->sqllog = $this->setSqlLog($debug);
        $this->demo = $this->setDemo($debug);
        $this->simple2fa = $this->setSimple2fa($debug);
    }

    /**
     * @param mixed[] $debug
     */
    private function setSql(array $debug): bool
    {
        return isset($debug['sql']) && $debug['sql'];
    }

    /**
     * @param mixed[] $debug
     */
    private function setSqlLog(array $debug): bool
    {
        return isset($debug['sqllog']) && $debug['sqllog'];
    }

    /**
     * @param mixed[] $debug
     */
    private function setDemo(array $debug): bool
    {
        return isset($debug['demo']) && $debug['demo'];
    }

    /**
     * @param mixed[] $debug
     */
    private function setSimple2fa(array $debug): bool
    {
        return isset($debug['simple2fa']) && $debug['simple2fa'];
    }
}
