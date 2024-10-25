<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\Concat;
use Psalm\Node\VirtualNode;

class VirtualConcat extends Concat implements VirtualNode
{

}
