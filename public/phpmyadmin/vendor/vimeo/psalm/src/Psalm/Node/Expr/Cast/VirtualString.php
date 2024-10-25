<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\Cast;

use PhpParser\Node\Expr\Cast\String_;
use Psalm\Node\VirtualNode;

class VirtualString extends String_ implements VirtualNode
{

}
