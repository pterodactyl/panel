<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Switch_;
use Psalm\Node\VirtualNode;

class VirtualSwitch extends Switch_ implements VirtualNode
{

}
