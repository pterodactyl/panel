<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use function array_filter;
use function array_keys;
use function array_reduce;
use function sprintf;
use const T_FUNCTION;

class FunctionLengthSniff implements Sniff
{

	public const CODE_FUNCTION_LENGTH = 'FunctionLength';

	/** @var int */
	public $maxLinesLength = 20;

	/** @var bool */
	public $includeComments = false;

	/** @var bool */
	public $includeWhitespace = false;

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [T_FUNCTION];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $functionPointer
	 */
	public function process(File $file, $functionPointer): void
	{
		$flags = array_keys(array_filter([
			FunctionHelper::LINE_INCLUDE_COMMENT => $this->includeComments,
			FunctionHelper::LINE_INCLUDE_WHITESPACE => $this->includeWhitespace,
		]));
		$flags = array_reduce($flags, static function ($carry, $flag): int {
			return $carry | $flag;
		}, 0);

		$length = FunctionHelper::getFunctionLengthInLines($file, $functionPointer, $flags);

		if ($length <= SniffSettingsHelper::normalizeInteger($this->maxLinesLength)) {
			return;
		}

		$errorMessage = sprintf(
			'Your function is too long. Currently using %d lines. Can be up to %d lines.',
			$length,
			$this->maxLinesLength
		);

		$file->addError($errorMessage, $functionPointer, self::CODE_FUNCTION_LENGTH);
	}

}
