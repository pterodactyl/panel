<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use function abs;
use function array_key_exists;
use function in_array;
use const T_CASE;
use const T_CLOSE_CURLY_BRACKET;
use const T_COLON;
use const T_DEFAULT;
use const T_OPEN_CURLY_BRACKET;
use const T_OPEN_TAG;
use const T_RETURN;
use const T_SEMICOLON;
use const T_SWITCH;
use const T_THROW;
use const T_YIELD;
use const T_YIELD_FROM;

class JumpStatementsSpacingSniff extends AbstractControlStructureSpacing
{

	/** @var int */
	public $linesCountBefore = 1;

	/** @var int */
	public $linesCountBeforeFirst = 0;

	/** @var int|null */
	public $linesCountBeforeWhenFirstInCaseOrDefault = null;

	/** @var int */
	public $linesCountAfter = 1;

	/** @var int */
	public $linesCountAfterLast = 0;

	/** @var int|null */
	public $linesCountAfterWhenLastInCaseOrDefault = null;

	/** @var int|null */
	public $linesCountAfterWhenLastInLastCaseOrDefault = null;

	/** @var bool */
	public $allowSingleLineYieldStacking = true;

	/** @var string[] */
	public $jumpStatements = [];

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $jumpStatementPointer
	 */
	public function process(File $phpcsFile, $jumpStatementPointer): void
	{
		if ($this->isOneOfYieldSpecialCases($phpcsFile, $jumpStatementPointer)) {
			return;
		}

		parent::process($phpcsFile, $jumpStatementPointer);
	}

	/**
	 * @return string[]
	 */
	protected function getSupportedKeywords(): array
	{
		return [
			self::KEYWORD_GOTO,
			self::KEYWORD_BREAK,
			self::KEYWORD_CONTINUE,
			self::KEYWORD_RETURN,
			self::KEYWORD_THROW,
			self::KEYWORD_YIELD,
			self::KEYWORD_YIELD_FROM,
		];
	}

	/**
	 * @return string[]
	 */
	protected function getKeywordsToCheck(): array
	{
		return $this->jumpStatements;
	}

	protected function getLinesCountBefore(): int
	{
		return SniffSettingsHelper::normalizeInteger($this->linesCountBefore);
	}

	protected function getLinesCountBeforeFirst(File $phpcsFile, int $jumpStatementPointer): int
	{
		if (
			$this->linesCountBeforeWhenFirstInCaseOrDefault !== null
			&& $this->isFirstInCaseOrDefault($phpcsFile, $jumpStatementPointer)
		) {
			return SniffSettingsHelper::normalizeInteger($this->linesCountBeforeWhenFirstInCaseOrDefault);
		}

		return SniffSettingsHelper::normalizeInteger($this->linesCountBeforeFirst);
	}

	protected function getLinesCountAfter(): int
	{
		return SniffSettingsHelper::normalizeInteger($this->linesCountAfter);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	protected function getLinesCountAfterLast(File $phpcsFile, int $jumpStatementPointer, int $jumpStatementEndPointer): int
	{
		if (
			$this->linesCountAfterWhenLastInLastCaseOrDefault !== null
			&& $this->isLastInLastCaseOrDefault($phpcsFile, $jumpStatementEndPointer)
		) {
			return SniffSettingsHelper::normalizeInteger($this->linesCountAfterWhenLastInLastCaseOrDefault);
		}

		if (
			$this->linesCountAfterWhenLastInCaseOrDefault !== null
			&& $this->isLastInCaseOrDefault($phpcsFile, $jumpStatementEndPointer)
		) {
			return SniffSettingsHelper::normalizeInteger($this->linesCountAfterWhenLastInCaseOrDefault);
		}

		return SniffSettingsHelper::normalizeInteger($this->linesCountAfterLast);
	}

	protected function checkLinesBefore(File $phpcsFile, int $jumpStatementPointer): void
	{
		if (
			$this->allowSingleLineYieldStacking
			&& $this->isStackedSingleLineYield($phpcsFile, $jumpStatementPointer, true)
		) {
			return;
		}

		if ($this->isThrowExpression($phpcsFile, $jumpStatementPointer)) {
			return;
		}

		parent::checkLinesBefore($phpcsFile, $jumpStatementPointer);
	}

	protected function checkLinesAfter(File $phpcsFile, int $jumpStatementPointer): void
	{
		if (
			$this->allowSingleLineYieldStacking
			&& $this->isStackedSingleLineYield($phpcsFile, $jumpStatementPointer, false)
		) {
			return;
		}

		if ($this->isThrowExpression($phpcsFile, $jumpStatementPointer)) {
			return;
		}

		parent::checkLinesAfter($phpcsFile, $jumpStatementPointer);
	}

	private function isOneOfYieldSpecialCases(File $phpcsFile, int $jumpStatementPointer): bool
	{
		$tokens = $phpcsFile->getTokens();

		$jumpStatementToken = $tokens[$jumpStatementPointer];
		if ($jumpStatementToken['code'] !== T_YIELD && $jumpStatementToken['code'] !== T_YIELD_FROM) {
			return false;
		}

		// check if yield is used inside parentheses (function call, while, ...)
		if (array_key_exists('nested_parenthesis', $jumpStatementToken)) {
			return true;
		}

		$pointerBefore = TokenHelper::findPreviousEffective($phpcsFile, $jumpStatementPointer - 1);

		// check if yield is used in assignment
		if (in_array($tokens[$pointerBefore]['code'], Tokens::$assignmentTokens, true)) {
			return true;
		}

		// check if yield is used in a return statement
		return $tokens[$pointerBefore]['code'] === T_RETURN;
	}

	private function isStackedSingleLineYield(File $phpcsFile, int $jumpStatementPointer, bool $previous): bool
	{
		$tokens = $phpcsFile->getTokens();
		$yields = [T_YIELD, T_YIELD_FROM];

		if (!in_array($tokens[$jumpStatementPointer]['code'], $yields, true)) {
			return false;
		}

		$adjoiningYieldPointer = $previous
			? TokenHelper::findPrevious($phpcsFile, $yields, $jumpStatementPointer - 1)
			: TokenHelper::findNext($phpcsFile, $yields, $jumpStatementPointer + 1);

		return $adjoiningYieldPointer !== null
			&& abs($tokens[$adjoiningYieldPointer]['line'] - $tokens[$jumpStatementPointer]['line']) === 1;
	}

	private function isThrowExpression(File $phpcsFile, int $jumpStatementPointer): bool
	{
		$tokens = $phpcsFile->getTokens();

		if ($tokens[$jumpStatementPointer]['code'] !== T_THROW) {
			return false;
		}

		$pointerBefore = TokenHelper::findPreviousEffective($phpcsFile, $jumpStatementPointer - 1);

		return !in_array(
			$tokens[$pointerBefore]['code'],
			[T_SEMICOLON, T_COLON, T_OPEN_CURLY_BRACKET, T_CLOSE_CURLY_BRACKET, T_OPEN_TAG],
			true
		);
	}

	private function isFirstInCaseOrDefault(File $phpcsFile, int $jumpStatementPointer): bool
	{
		$tokens = $phpcsFile->getTokens();

		$previousPointer = TokenHelper::findPreviousEffective($phpcsFile, $jumpStatementPointer - 1);

		if ($tokens[$previousPointer]['code'] !== T_COLON) {
			return false;
		}

		$firstPointerOnLine = TokenHelper::findFirstNonWhitespaceOnLine($phpcsFile, $previousPointer);

		return in_array($tokens[$firstPointerOnLine]['code'], [T_CASE, T_DEFAULT], true);
	}

	private function isLastInCaseOrDefault(File $phpcsFile, int $jumpStatementEndPointer): bool
	{
		$tokens = $phpcsFile->getTokens();

		$nextPointer = TokenHelper::findNextEffective($phpcsFile, $jumpStatementEndPointer + 1);

		if (in_array($tokens[$nextPointer]['code'], [T_CASE, T_DEFAULT], true)) {
			return true;
		}

		return $tokens[$nextPointer]['code'] === T_CLOSE_CURLY_BRACKET
			&& array_key_exists('scope_condition', $tokens[$nextPointer])
			&& $tokens[$tokens[$nextPointer]['scope_condition']]['code'] === T_SWITCH;
	}

	private function isLastInLastCaseOrDefault(File $phpcsFile, int $jumpStatementEndPointer): bool
	{
		if (!$this->isLastInCaseOrDefault($phpcsFile, $jumpStatementEndPointer)) {
			return false;
		}

		$nextPointer = TokenHelper::findNextEffective($phpcsFile, $jumpStatementEndPointer + 1);

		return !in_array($phpcsFile->getTokens()[$nextPointer]['code'], [T_CASE, T_DEFAULT], true);
	}

}
