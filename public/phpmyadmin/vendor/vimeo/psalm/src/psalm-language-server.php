<?php

/**
 * @deprecated This file is going to be removed in Psalm 5
 */
use Psalm\Internal\Cli\LanguageServer;

/** */
require_once __DIR__ . '/Psalm/Internal/Cli/LanguageServer.php';
LanguageServer::run($argv);
