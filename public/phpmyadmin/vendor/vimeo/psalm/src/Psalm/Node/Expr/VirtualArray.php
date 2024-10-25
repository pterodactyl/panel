<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Array_;
use Psalm\Node\VirtualNode;

class VirtualArray extends Array_ implements VirtualNode
{

}
