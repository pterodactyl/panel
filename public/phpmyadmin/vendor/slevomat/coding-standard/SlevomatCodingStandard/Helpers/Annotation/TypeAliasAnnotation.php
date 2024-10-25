<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Helpers\Annotation;

use InvalidArgumentException;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use SlevomatCodingStandard\Helpers\AnnotationTypeHelper;
use function in_array;
use function sprintf;

/**
 * @internal
 */
class TypeAliasAnnotation extends Annotation
{

	/** @var TypeAliasTagValueNode|null */
	private $contentNode;

	public function __construct(string $name, int $startPointer, int $endPointer, ?string $content, ?TypeAliasTagValueNode $contentNode)
	{
		if (!in_array(
			$name,
			['@psalm-type', '@phpstan-type'],
			true
		)) {
			throw new InvalidArgumentException(sprintf('Unsupported annotation %s.', $name));
		}

		parent::__construct($name, $startPointer, $endPointer, $content);

		$this->contentNode = $contentNode;
	}

	public function isInvalid(): bool
	{
		return $this->contentNode === null;
	}

	public function getContentNode(): TypeAliasTagValueNode
	{
		$this->errorWhenInvalid();

		return $this->contentNode;
	}

	public function getAlias(): string
	{
		$this->errorWhenInvalid();

		return $this->contentNode->alias;
	}

	public function getType(): TypeNode
	{
		$this->errorWhenInvalid();

		return $this->contentNode->type;
	}

	public function export(): string
	{
		return sprintf('%s %s %s', $this->name, $this->contentNode->alias, AnnotationTypeHelper::export($this->contentNode->type));
	}

}
