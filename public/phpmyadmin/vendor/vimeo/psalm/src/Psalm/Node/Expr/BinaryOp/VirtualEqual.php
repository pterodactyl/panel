<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Equal;
use Psalm\Node\VirtualNode;

class VirtualEqual extends Equal implements VirtualNode
{

}
