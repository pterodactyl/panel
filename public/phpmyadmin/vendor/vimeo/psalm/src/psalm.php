<?php

/**
 * @deprecated This file is going to be removed in Psalm 5
 */
use Psalm\Internal\Cli\Psalm;

/** */
require __DIR__ . '/Psalm/Internal/Cli/Psalm.php';
Psalm::run($argv);
