<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\TryCatch;
use Psalm\Node\VirtualNode;

class VirtualTryCatch extends TryCatch implements VirtualNode
{

}
