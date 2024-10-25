<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar\MagicConst;

use PhpParser\Node\Scalar\MagicConst\Class_;
use Psalm\Node\VirtualNode;

class VirtualClass extends Class_ implements VirtualNode
{

}
