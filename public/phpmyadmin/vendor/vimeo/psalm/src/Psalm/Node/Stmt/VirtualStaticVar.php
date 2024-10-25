<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\StaticVar;
use Psalm\Node\VirtualNode;

class VirtualStaticVar extends StaticVar implements VirtualNode
{

}
