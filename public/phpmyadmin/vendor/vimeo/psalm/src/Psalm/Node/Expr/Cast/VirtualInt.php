<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\Cast;

use PhpParser\Node\Expr\Cast\Int_;
use Psalm\Node\VirtualNode;

class VirtualInt extends Int_ implements VirtualNode
{

}
