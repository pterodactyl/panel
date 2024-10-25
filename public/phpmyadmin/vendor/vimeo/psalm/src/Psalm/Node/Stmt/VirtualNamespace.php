<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Namespace_;
use Psalm\Node\VirtualNode;

class VirtualNamespace extends Namespace_ implements VirtualNode
{

}
