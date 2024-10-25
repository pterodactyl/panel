<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\TraitUse;
use Psalm\Node\VirtualNode;

class VirtualTraitUse extends TraitUse implements VirtualNode
{

}
