<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Psalm\Node\VirtualNode;

class VirtualBooleanOr extends BooleanOr implements VirtualNode
{

}
