<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;
use function count;
use function in_array;
use const T_CASE;
use const T_CLOSE_PARENTHESIS;
use const T_CLOSE_SHORT_ARRAY;
use const T_CLOSE_SQUARE_BRACKET;
use const T_COALESCE;
use const T_COLON;
use const T_COMMA;
use const T_DOUBLE_ARROW;
use const T_EQUAL;
use const T_INLINE_ELSE;
use const T_INLINE_THEN;
use const T_OPEN_PARENTHESIS;
use const T_OPEN_SHORT_ARRAY;
use const T_OPEN_SQUARE_BRACKET;
use const T_OPEN_TAG;
use const T_OPEN_TAG_WITH_ECHO;
use const T_RETURN;
use const T_SEMICOLON;
use const T_THROW;

/**
 * @internal
 */
class TernaryOperatorHelper
{

	public static function isConditionOfTernaryOperator(File $phpcsFile, int $pointer): bool
	{
		$inlineThenPointer = TokenHelper::findNext($phpcsFile, T_INLINE_THEN, $pointer + 1);

		if ($inlineThenPointer === null) {
			return false;
		}

		$startPointer = self::getStartPointer($phpcsFile, $inlineThenPointer);

		return $startPointer <= $pointer;
	}

	public static function getElsePointer(File $phpcsFile, int $inlineThenPointer): int
	{
		$tokens = $phpcsFile->getTokens();

		$pointer = $inlineThenPointer;
		do {
			$pointer = TokenHelper::findNext(
				$phpcsFile,
				[T_INLINE_ELSE, T_OPEN_PARENTHESIS, T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET],
				$pointer + 1
			);

			if ($tokens[$pointer]['code'] === T_OPEN_PARENTHESIS) {
				$pointer = $tokens[$pointer]['parenthesis_closer'];
				continue;
			}

			if (in_array($tokens[$pointer]['code'], [T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET], true)) {
				$pointer = $tokens[$pointer]['bracket_closer'];
				continue;
			}

			if (ScopeHelper::isInSameScope($phpcsFile, $inlineThenPointer, $pointer)) {
				break;
			}
		} while ($pointer !== null);

		return $pointer;
	}

	public static function getStartPointer(File $phpcsFile, int $inlineThenPointer): int
	{
		$tokens = $phpcsFile->getTokens();

		$pointerBeforeCondition = $inlineThenPointer;
		do {
			$pointerBeforeCondition = TokenHelper::findPrevious(
				$phpcsFile,
				[T_EQUAL, T_DOUBLE_ARROW, T_COMMA, T_RETURN, T_THROW, T_CASE, T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_OPEN_SQUARE_BRACKET, T_OPEN_SHORT_ARRAY, T_OPEN_PARENTHESIS],
				$pointerBeforeCondition - 1
			);

			if (
				in_array($tokens[$pointerBeforeCondition]['code'], [T_OPEN_SQUARE_BRACKET, T_OPEN_SHORT_ARRAY], true)
				&& $tokens[$pointerBeforeCondition]['bracket_closer'] < $inlineThenPointer
			) {
				continue;
			}

			if (
				$tokens[$pointerBeforeCondition]['code'] === T_OPEN_PARENTHESIS
				&& $tokens[$pointerBeforeCondition]['parenthesis_closer'] < $inlineThenPointer
			) {
				continue;
			}

			break;

		} while (true);

		return TokenHelper::findNextEffective($phpcsFile, $pointerBeforeCondition + 1);
	}

	public static function getEndPointer(File $phpcsFile, int $inlineThenPointer, int $inlineElsePointer): int
	{
		$tokens = $phpcsFile->getTokens();

		$pointerAfterInlineElseEnd = $inlineElsePointer;
		do {
			$pointerAfterInlineElseEnd = TokenHelper::findNext(
				$phpcsFile,
				[T_SEMICOLON, T_COLON, T_COMMA, T_DOUBLE_ARROW, T_CLOSE_PARENTHESIS, T_CLOSE_SHORT_ARRAY, T_CLOSE_SQUARE_BRACKET, T_COALESCE],
				$pointerAfterInlineElseEnd + 1
			);

			if ($pointerAfterInlineElseEnd === null) {
				continue;
			}

			if ($tokens[$pointerAfterInlineElseEnd]['code'] === T_CLOSE_PARENTHESIS) {
				if ($tokens[$pointerAfterInlineElseEnd]['parenthesis_opener'] < $inlineThenPointer) {
					break;
				}
			} elseif (in_array($tokens[$pointerAfterInlineElseEnd]['code'], [T_CLOSE_SHORT_ARRAY, T_CLOSE_SQUARE_BRACKET], true)) {
				if ($tokens[$pointerAfterInlineElseEnd]['bracket_opener'] < $inlineThenPointer) {
					break;
				}
			} elseif ($tokens[$pointerAfterInlineElseEnd]['code'] === T_COMMA) {
				$previousPointer = TokenHelper::findPrevious(
					$phpcsFile,
					[T_OPEN_PARENTHESIS, T_OPEN_SHORT_ARRAY],
					$pointerAfterInlineElseEnd - 1,
					$inlineThenPointer
				);

				if ($previousPointer === null) {
					break;
				}

				if (
					$tokens[$previousPointer]['code'] === T_OPEN_PARENTHESIS
					&& $tokens[$previousPointer]['parenthesis_closer'] < $pointerAfterInlineElseEnd
				) {
					break;
				}

				if (
					$tokens[$previousPointer]['code'] === T_OPEN_SHORT_ARRAY
					&& $tokens[$previousPointer]['bracket_closer'] < $pointerAfterInlineElseEnd
				) {
					break;
				}

			} elseif ($tokens[$pointerAfterInlineElseEnd]['code'] === T_DOUBLE_ARROW) {
				$previousPointer = TokenHelper::findPrevious(
					$phpcsFile,
					T_OPEN_SHORT_ARRAY,
					$pointerAfterInlineElseEnd - 1,
					$inlineThenPointer
				);
				if ($previousPointer === null) {
					break;
				}
			} elseif (ScopeHelper::isInSameScope($phpcsFile, $inlineElsePointer, $pointerAfterInlineElseEnd)) {
				break;
			}
		} while ($pointerAfterInlineElseEnd !== null);

		if ($pointerAfterInlineElseEnd !== null) {
			return TokenHelper::findPreviousEffective($phpcsFile, $pointerAfterInlineElseEnd - 1);
		}

		return TokenHelper::findPreviousEffective($phpcsFile, count($tokens) - 1);
	}

}
