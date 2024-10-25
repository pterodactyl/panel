<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar;

use PhpParser\Node\Scalar\LNumber;
use Psalm\Node\VirtualNode;

class VirtualLNumber extends LNumber implements VirtualNode
{

}
