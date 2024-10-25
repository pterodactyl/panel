<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\ShiftLeft;
use Psalm\Node\VirtualNode;

class VirtualShiftLeft extends ShiftLeft implements VirtualNode
{

}
