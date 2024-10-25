<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Helpers;

use function strpos;
use function substr;

/**
 * @internal
 */
class TypeHint
{

	/** @var string */
	private $typeHint;

	/** @var bool */
	private $nullable;

	/** @var int */
	private $startPointer;

	/** @var int */
	private $endPointer;

	public function __construct(string $typeHint, bool $nullable, int $startPointer, int $endPointer)
	{
		$this->typeHint = $typeHint;
		$this->nullable = $nullable;
		$this->startPointer = $startPointer;
		$this->endPointer = $endPointer;
	}

	public function getTypeHint(): string
	{
		return $this->typeHint;
	}

	public function getTypeHintWithoutNullabilitySymbol(): string
	{
		return strpos($this->typeHint, '?') === 0 ? substr($this->typeHint, 1) : $this->typeHint;
	}

	public function isNullable(): bool
	{
		return $this->nullable;
	}

	public function getStartPointer(): int
	{
		return $this->startPointer;
	}

	public function getEndPointer(): int
	{
		return $this->endPointer;
	}

}
