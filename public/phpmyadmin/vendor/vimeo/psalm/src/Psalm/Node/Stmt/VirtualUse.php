<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Use_;
use Psalm\Node\VirtualNode;

class VirtualUse extends Use_ implements VirtualNode
{

}
