<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use Psalm\Node\VirtualNode;

class VirtualGreaterOrEqual extends GreaterOrEqual implements VirtualNode
{

}
