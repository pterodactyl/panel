<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Global_;
use Psalm\Node\VirtualNode;

class VirtualGlobal extends Global_ implements VirtualNode
{

}
