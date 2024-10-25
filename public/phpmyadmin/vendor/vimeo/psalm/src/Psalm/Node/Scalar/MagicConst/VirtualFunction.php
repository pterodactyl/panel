<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar\MagicConst;

use PhpParser\Node\Scalar\MagicConst\Function_;
use Psalm\Node\VirtualNode;

class VirtualFunction extends Function_ implements VirtualNode
{

}
