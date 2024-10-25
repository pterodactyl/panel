<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;
use function array_merge;
use function in_array;
use const T_BITWISE_OR;
use const T_CATCH;
use const T_FINALLY;

/**
 * @internal
 */
class CatchHelper
{

	public static function getTryEndPointer(File $phpcsFile, int $catchPointer): int
	{
		$tokens = $phpcsFile->getTokens();

		$endPointer = $tokens[$catchPointer]['scope_closer'];

		do {
			$nextPointer = TokenHelper::findNextEffective($phpcsFile, $endPointer + 1);

			if ($nextPointer === null || !in_array($tokens[$nextPointer]['code'], [T_CATCH, T_FINALLY], true)) {
				break;
			}

			$endPointer = $tokens[$nextPointer]['scope_closer'];

		} while (true);

		return $endPointer;
	}

	/**
	 * @param array<string, array<int, int|string>|int|string> $catchToken
	 * @return string[]
	 */
	public static function findCatchedTypesInCatch(File $phpcsFile, array $catchToken): array
	{
		/** @var int $catchParenthesisOpenerPointer */
		$catchParenthesisOpenerPointer = $catchToken['parenthesis_opener'];
		/** @var int $catchParenthesisCloserPointer */
		$catchParenthesisCloserPointer = $catchToken['parenthesis_closer'];

		$nameTokenCodes = TokenHelper::getNameTokenCodes();

		$nameEndPointer = $catchParenthesisOpenerPointer;
		$tokens = $phpcsFile->getTokens();
		$catchedTypes = [];
		do {
			$nameStartPointer = TokenHelper::findNext(
				$phpcsFile,
				array_merge([T_BITWISE_OR], $nameTokenCodes),
				$nameEndPointer + 1,
				$catchParenthesisCloserPointer
			);
			if ($nameStartPointer === null) {
				break;
			}

			if ($tokens[$nameStartPointer]['code'] === T_BITWISE_OR) {
				/** @var int $nameStartPointer */
				$nameStartPointer = TokenHelper::findNextEffective($phpcsFile, $nameStartPointer + 1, $catchParenthesisCloserPointer);
			}

			$pointerAfterNameEndPointer = TokenHelper::findNextExcluding($phpcsFile, $nameTokenCodes, $nameStartPointer + 1);
			$nameEndPointer = $pointerAfterNameEndPointer === null ? $nameStartPointer : $pointerAfterNameEndPointer - 1;

			$catchedTypes[] = NamespaceHelper::resolveClassName(
				$phpcsFile,
				TokenHelper::getContent($phpcsFile, $nameStartPointer, $nameEndPointer),
				$catchParenthesisOpenerPointer
			);
		} while (true);

		return $catchedTypes;
	}

}
