<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\BitwiseXor;
use Psalm\Node\VirtualNode;

class VirtualBitwiseXor extends BitwiseXor implements VirtualNode
{

}
