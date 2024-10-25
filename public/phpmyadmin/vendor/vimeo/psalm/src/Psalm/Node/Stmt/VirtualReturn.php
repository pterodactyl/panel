<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Return_;
use Psalm\Node\VirtualNode;

class VirtualReturn extends Return_ implements VirtualNode
{

}
