<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\Cast;

use PhpParser\Node\Expr\Cast\Double;
use Psalm\Node\VirtualNode;

class VirtualDouble extends Double implements VirtualNode
{

}
