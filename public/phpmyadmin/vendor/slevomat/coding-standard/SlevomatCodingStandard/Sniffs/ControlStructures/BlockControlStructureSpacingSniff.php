<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use function array_key_exists;
use const T_CLOSE_CURLY_BRACKET;
use const T_DO;
use const T_WHILE;

class BlockControlStructureSpacingSniff extends AbstractControlStructureSpacing
{

	/** @var int */
	public $linesCountBefore = 1;

	/** @var int */
	public $linesCountBeforeFirst = 0;

	/** @var int */
	public $linesCountAfter = 1;

	/** @var int */
	public $linesCountAfterLast = 0;

	/** @var string[] */
	public $controlStructures = [];

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $controlStructurePointer
	 */
	public function process(File $phpcsFile, $controlStructurePointer): void
	{
		if ($this->isWhilePartOfDo($phpcsFile, $controlStructurePointer)) {
			return;
		}

		parent::process($phpcsFile, $controlStructurePointer);
	}

	/**
	 * @return string[]
	 */
	protected function getSupportedKeywords(): array
	{
		return [
			self::KEYWORD_IF,
			self::KEYWORD_DO,
			self::KEYWORD_WHILE,
			self::KEYWORD_FOR,
			self::KEYWORD_FOREACH,
			self::KEYWORD_SWITCH,
			self::KEYWORD_TRY,
			self::KEYWORD_CASE,
			self::KEYWORD_DEFAULT,
		];
	}

	/**
	 * @return string[]
	 */
	protected function getKeywordsToCheck(): array
	{
		return $this->controlStructures;
	}

	protected function getLinesCountBefore(): int
	{
		return SniffSettingsHelper::normalizeInteger($this->linesCountBefore);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	protected function getLinesCountBeforeFirst(File $phpcsFile, int $controlStructurePointer): int
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
	protected function getLinesCountAfterLast(File $phpcsFile, int $controlStructurePointer, int $controlStructureEndPointer): int
	{
		return SniffSettingsHelper::normalizeInteger($this->linesCountAfterLast);
	}

	private function isWhilePartOfDo(File $phpcsFile, int $controlStructurePointer): bool
	{
		$tokens = $phpcsFile->getTokens();
		$pointerBefore = TokenHelper::findPreviousEffective($phpcsFile, $controlStructurePointer - 1);

		return
			$tokens[$controlStructurePointer]['code'] === T_WHILE
			&& $tokens[$pointerBefore]['code'] === T_CLOSE_CURLY_BRACKET
			&& array_key_exists('scope_condition', $tokens[$pointerBefore])
			&& $tokens[$tokens[$pointerBefore]['scope_condition']]['code'] === T_DO;
	}

}
