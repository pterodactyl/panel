<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Expression;
use Psalm\Node\VirtualNode;

/**
 * Represents statements of type "expr;"
 */
class VirtualExpression extends Expression implements VirtualNode
{

}
