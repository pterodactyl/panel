<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\IndentationHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use function array_merge;
use function rtrim;
use function trim;
use const T_CLOSE_PARENTHESIS;
use const T_COMMA;
use const T_FUNCTION;
use const T_OPEN_PARENTHESIS;
use const T_PARENT;
use const T_SELF;
use const T_STATIC;
use const T_WHITESPACE;

abstract class AbstractLineCall implements Sniff
{

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return array_merge(TokenHelper::getOnlyNameTokenCodes(), [T_SELF, T_STATIC, T_PARENT]);
	}

	protected function isCall(File $phpcsFile, int $stringPointer): bool
	{
		$tokens = $phpcsFile->getTokens();

		$nextPointer = TokenHelper::findNextEffective($phpcsFile, $stringPointer + 1);

		if ($tokens[$nextPointer]['code'] !== T_OPEN_PARENTHESIS) {
			return false;
		}

		$previousPointer = TokenHelper::findPreviousEffective($phpcsFile, $stringPointer - 1);

		return $tokens[$previousPointer]['code'] !== T_FUNCTION;
	}

	protected function getLineStart(File $phpcsFile, int $pointer): string
	{
		$firstPointerOnLine = TokenHelper::findFirstTokenOnLine($phpcsFile, $pointer);

		return IndentationHelper::convertTabsToSpaces($phpcsFile, TokenHelper::getContent($phpcsFile, $firstPointerOnLine, $pointer));
	}

	protected function getCall(File $phpcsFile, int $parenthesisOpenerPointer, int $parenthesisCloserPointer): string
	{
		$tokens = $phpcsFile->getTokens();

		$pointerBeforeParenthesisCloser = TokenHelper::findPreviousEffective($phpcsFile, $parenthesisCloserPointer - 1);

		$endPointer = $tokens[$pointerBeforeParenthesisCloser]['code'] === T_COMMA
			? $pointerBeforeParenthesisCloser
			: $parenthesisCloserPointer;

		$call = '';

		for ($i = $parenthesisOpenerPointer + 1; $i < $endPointer; $i++) {
			if ($tokens[$i]['code'] === T_COMMA) {
				$nextPointer = TokenHelper::findNextEffective($phpcsFile, $i + 1);
				if ($tokens[$nextPointer]['code'] === T_CLOSE_PARENTHESIS) {
					$i = $nextPointer - 1;
					continue;
				}
			}

			if ($tokens[$i]['code'] === T_WHITESPACE) {
				if ($tokens[$i]['content'] === $phpcsFile->eolChar) {
					if ($tokens[$i - 1]['code'] === T_COMMA) {
						$call .= ' ';
					}

					continue;

				} if ($tokens[$i]['column'] === 1) {
					// Nothing
					continue;
				}
			}

			$call .= $tokens[$i]['content'];
		}

		return trim($call);
	}

	protected function getLineEnd(File $phpcsFile, int $pointer): string
	{
		$firstPointerOnNextLine = TokenHelper::findFirstTokenOnNextLine($phpcsFile, $pointer);

		return rtrim(TokenHelper::getContent($phpcsFile, $pointer, $firstPointerOnNextLine - 1));
	}

}
