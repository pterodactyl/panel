<?php declare(strict_types = 1);

namespace PHPStan\PhpDocParser\Ast\PhpDoc;

use PHPStan\PhpDocParser\Ast\NodeAttributes;
use function trim;

class PhpDocTagNode implements PhpDocChildNode
{

	use NodeAttributes;

	/** @var string */
	public $name;

	/** @var PhpDocTagValueNode */
	public $value;

	public function __construct(string $name, PhpDocTagValueNode $value)
	{
		$this->name = $name;
		$this->value = $value;
	}


	public function __toString(): string
	{
		return trim("{$this->name} {$this->value}");
	}

}
