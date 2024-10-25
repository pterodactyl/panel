<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\Cast;

use PhpParser\Node\Expr\Cast\Array_;
use Psalm\Node\VirtualNode;

class VirtualArray extends Array_ implements VirtualNode
{

}
