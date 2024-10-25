<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Helpers\Annotation;

use InvalidArgumentException;
use PHPStan\PhpDocParser\Ast\PhpDoc\AssertTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use SlevomatCodingStandard\Helpers\AnnotationTypeHelper;
use function in_array;
use function sprintf;

/**
 * @internal
 */
class AssertAnnotation extends Annotation
{

	/** @var AssertTagValueNode|null */
	private $contentNode;

	public function __construct(string $name, int $startPointer, int $endPointer, ?string $content, ?AssertTagValueNode $contentNode)
	{
		if (!in_array(
			$name,
			['@phpstan-assert', '@phpstan-assert-if-true', '@phpstan-assert-if-false', '@psalm-assert', '@psalm-assert-if-true', '@psalm-assert-if-false'],
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

	public function getContentNode(): AssertTagValueNode
	{
		$this->errorWhenInvalid();

		return $this->contentNode;
	}

	public function hasDescription(): bool
	{
		return $this->getDescription() !== null;
	}

	public function getDescription(): ?string
	{
		$this->errorWhenInvalid();

		return $this->contentNode->description !== '' ? $this->contentNode->description : null;
	}

	public function getType(): TypeNode
	{
		$this->errorWhenInvalid();

		return $this->contentNode->type;
	}

	public function export(): string
	{
		$exported = sprintf('%s %s %s', $this->name, AnnotationTypeHelper::export($this->getType()), $this->contentNode->parameter);

		$description = $this->getDescription();
		if ($description !== null) {
			$exported .= sprintf(' %s', $this->fixDescription($description));
		}

		return $exported;
	}

}
