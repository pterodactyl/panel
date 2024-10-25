<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\TernaryOperatorHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use function ltrim;
use function sprintf;
use function trim;
use const T_BOOLEAN_NOT;
use const T_INLINE_ELSE;
use const T_INLINE_THEN;

class RequireShortTernaryOperatorSniff implements Sniff
{

	public const CODE_REQUIRED_SHORT_TERNARY_OPERATOR = 'RequiredShortTernaryOperator';

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [
			T_INLINE_THEN,
		];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $inlineThenPointer
	 */
	public function process(File $phpcsFile, $inlineThenPointer): void
	{
		$tokens = $phpcsFile->getTokens();

		$nextPointer = TokenHelper::findNextEffective($phpcsFile, $inlineThenPointer + 1);

		if ($tokens[$nextPointer]['code'] === T_INLINE_ELSE) {
			return;
		}

		$conditionStartPointer = TernaryOperatorHelper::getStartPointer($phpcsFile, $inlineThenPointer);
		$inlineElsePointer = TernaryOperatorHelper::getElsePointer($phpcsFile, $inlineThenPointer);
		$inlineElseEndPointer = TernaryOperatorHelper::getEndPointer($phpcsFile, $inlineThenPointer, $inlineElsePointer);

		$thenContent = trim(TokenHelper::getContent($phpcsFile, $inlineThenPointer + 1, $inlineElsePointer - 1));
		$elseContent = trim(TokenHelper::getContent($phpcsFile, $inlineElsePointer + 1, $inlineElseEndPointer));

		$conditionEndPointer = TokenHelper::findPreviousEffective($phpcsFile, $inlineThenPointer - 1);

		$condition = TokenHelper::getContent($phpcsFile, $conditionStartPointer, $conditionEndPointer);

		if ($tokens[$conditionStartPointer]['code'] === T_BOOLEAN_NOT) {
			if ($elseContent !== ltrim($condition, '!')) {
				return;
			}
		} else {
			if ($thenContent !== $condition) {
				return;
			}
		}

		$fix = $phpcsFile->addFixableError('Use short ternary operator.', $inlineThenPointer, self::CODE_REQUIRED_SHORT_TERNARY_OPERATOR);

		if (!$fix) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();

		if ($tokens[$conditionStartPointer]['code'] === T_BOOLEAN_NOT) {
			$phpcsFile->fixer->replaceToken($conditionStartPointer, '');

			for ($i = $inlineThenPointer + 1; $i <= $inlineElseEndPointer; $i++) {
				$phpcsFile->fixer->replaceToken($i, '');
			}

			$phpcsFile->fixer->addContent($inlineThenPointer, sprintf(': %s', $thenContent));

		} else {
			for ($i = $inlineThenPointer + 1; $i < $inlineElsePointer; $i++) {
				$phpcsFile->fixer->replaceToken($i, '');
			}
		}

		$phpcsFile->fixer->endChangeset();
	}

}
