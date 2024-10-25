<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\PropertyProperty;
use Psalm\Node\VirtualNode;

class VirtualPropertyProperty extends PropertyProperty implements VirtualNode
{

}
