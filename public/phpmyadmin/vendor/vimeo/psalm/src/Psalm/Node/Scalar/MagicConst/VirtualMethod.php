<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar\MagicConst;

use PhpParser\Node\Scalar\MagicConst\Method;
use Psalm\Node\VirtualNode;

class VirtualMethod extends Method implements VirtualNode
{

}
