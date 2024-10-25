<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\SysInfo;

use const PHP_OS;

/**
 * Basic SysInfo class not providing any real data.
 */
class Base
{
    /**
     * The OS name
     *
     * @var string
     */
    public $os = PHP_OS;

    /**
     * Gets load information
     *
     * @return array with load data
     */
    public function loadavg()
    {
        return ['loadavg' => 0];
    }

    /**
     * Gets information about memory usage
     *
     * @return array with memory usage data
     */
    public function memory()
    {
        return [];
    }

    /**
     * Checks whether class is supported in this environment
     */
    public function supported(): bool
    {
        return true;
    }
}
