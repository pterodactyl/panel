<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Error;
use Psalm\Node\VirtualNode;

/**
 * Error node used during parsing with error recovery.
 *
 * An error node may be placed at a position where an expression is required, but an error occurred.
 * Error nodes will not be present if the parser is run in throwOnError mode (the default).
 */
class VirtualError extends Error implements VirtualNode
{

}
