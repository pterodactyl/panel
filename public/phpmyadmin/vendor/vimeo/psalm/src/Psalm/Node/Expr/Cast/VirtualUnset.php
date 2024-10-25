<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\Cast;

use PhpParser\Node\Expr\Cast\Unset_;
use Psalm\Node\VirtualNode;

class VirtualUnset extends Unset_ implements VirtualNode
{

}
