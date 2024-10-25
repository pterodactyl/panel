<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\Div;
use Psalm\Node\VirtualNode;

class VirtualDiv extends Div implements VirtualNode
{

}
