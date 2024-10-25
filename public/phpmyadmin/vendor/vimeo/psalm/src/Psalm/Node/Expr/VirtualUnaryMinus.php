<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\UnaryMinus;
use Psalm\Node\VirtualNode;

class VirtualUnaryMinus extends UnaryMinus implements VirtualNode
{

}
