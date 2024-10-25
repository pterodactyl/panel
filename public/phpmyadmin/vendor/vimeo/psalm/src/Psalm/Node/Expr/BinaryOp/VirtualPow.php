<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Pow;
use Psalm\Node\VirtualNode;

class VirtualPow extends Pow implements VirtualNode
{

}
