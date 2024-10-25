<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\ShiftRight;
use Psalm\Node\VirtualNode;

class VirtualShiftRight extends ShiftRight implements VirtualNode
{

}
