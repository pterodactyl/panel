<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\InlineHTML;
use Psalm\Node\VirtualNode;

class VirtualInlineHTML extends InlineHTML implements VirtualNode
{

}
