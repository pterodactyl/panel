<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use Psalm\Node\VirtualNode;

class VirtualBooleanAnd extends BooleanAnd implements VirtualNode
{

}
