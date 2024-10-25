<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar;

use PhpParser\Node\Scalar\EncapsedStringPart;
use Psalm\Node\VirtualNode;

class VirtualEncapsedStringPart extends EncapsedStringPart implements VirtualNode
{

}
