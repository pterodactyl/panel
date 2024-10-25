<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\Pow;
use Psalm\Node\VirtualNode;

class VirtualPow extends Pow implements VirtualNode
{

}
