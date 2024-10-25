<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Concat;
use Psalm\Node\VirtualNode;

class VirtualConcat extends Concat implements VirtualNode
{

}
