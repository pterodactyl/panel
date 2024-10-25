<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\ArrowFunction;
use Psalm\Node\VirtualNode;

class VirtualArrowFunction extends ArrowFunction implements VirtualNode
{

}
