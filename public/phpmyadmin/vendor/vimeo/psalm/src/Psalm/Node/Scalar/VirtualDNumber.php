<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar;

use PhpParser\Node\Scalar\DNumber;
use Psalm\Node\VirtualNode;

class VirtualDNumber extends DNumber implements VirtualNode
{

}
