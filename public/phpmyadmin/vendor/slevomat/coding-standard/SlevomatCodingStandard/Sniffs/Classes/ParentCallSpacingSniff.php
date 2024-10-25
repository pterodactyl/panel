<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Sniffs\ControlStructures\AbstractControlStructureSpacing;
use function array_key_exists;
use function array_merge;
use function in_array;
use const T_ASPERAND;
use const T_COALESCE;
use const T_COLON;
use const T_INLINE_ELSE;
use const T_INLINE_THEN;
use const T_OPEN_SHORT_ARRAY;
use const T_RETURN;
use const T_STRING_CONCAT;
use const T_YIELD;
use const T_YIELD_FROM;

class ParentCallSpacingSniff extends AbstractControlStructureSpacing
{

	/** @var int */
	public $linesCountBefore = 1;

	/** @var int */
	public $linesCountBeforeFirst = 0;

	/** @var int */
	public $linesCountAfter = 1;

	/** @var int */
	public $linesCountAfterLast = 0;

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $parentPointer
	 */
	public function process(File $phpcsFile, $parentPointer): void
	{
		$tokens = $phpcsFile->getTokens();

		if (array_key_exists('nested_parenthesis', $tokens[$parentPointer])) {
			return;
		}

		$previousPointer = TokenHelper::findPreviousEffective($phpcsFile, $parentPointer - 1);
		if (in_array($tokens[$previousPointer]['code'], array_merge(Tokens::$castTokens, [T_ASPERAND]), true)) {
			$previousPointer = TokenHelper::findPreviousEffective($phpcsFile, $previousPointer - 1);
		}

		$tokensToIgnore = array_merge(
			Tokens::$assignmentTokens,
			Tokens::$equalityTokens,
			Tokens::$booleanOperators,
			[T_RETURN, T_YIELD, T_YIELD_FROM, T_COLON, T_STRING_CONCAT, T_INLINE_THEN, T_INLINE_ELSE, T_COALESCE]
		);
		if (in_array($tokens[$previousPointer]['code'], $tokensToIgnore, true)) {
			return;
		}

		$previousShortArrayOpenerPointer = TokenHelper::findPrevious($phpcsFile, T_OPEN_SHORT_ARRAY, $parentPointer - 1);
		if ($previousShortArrayOpenerPointer !== null && $tokens[$previousShortArrayOpenerPointer]['bracket_closer'] > $parentPointer) {
			return;
		}

		parent::process($phpcsFile, $parentPointer);
	}

	/**
	 * @return string[]
	 */
	protected function getSupportedKeywords(): array
	{
		return [self::KEYWORD_PARENT];
	}

	/**
	 * @return string[]
	 */
	protected function getKeywordsToCheck(): array
	{
		return [self::KEYWORD_PARENT];
	}

	protected function getLinesCountBefore(): int
	{
		return SniffSettingsHelper::normalizeInteger($this->linesCountBefore);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	protected function getLinesCountBeforeFirst(File $phpcsFile, int $parentPointer): int
	{
		return SniffSettingsHelper::normalizeInteger($this->linesCountBeforeFirst);
	}

	protected function getLinesCountAfter(): int
	{
		return SniffSettingsHelper::normalizeInteger($this->linesCountAfter);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	protected function getLinesCountAfterLast(File $phpcsFile, int $parentPointer, int $parentEndPointer): int
	{
		return SniffSettingsHelper::normalizeInteger($this->linesCountAfterLast);
	}

}
