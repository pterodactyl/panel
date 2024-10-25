<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\BitwiseXor;
use Psalm\Node\VirtualNode;

class VirtualBitwiseXor extends BitwiseXor implements VirtualNode
{

}
