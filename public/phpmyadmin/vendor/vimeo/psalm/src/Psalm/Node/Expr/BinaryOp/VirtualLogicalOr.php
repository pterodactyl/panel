<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use Psalm\Node\VirtualNode;

class VirtualLogicalOr extends LogicalOr implements VirtualNode
{

}
