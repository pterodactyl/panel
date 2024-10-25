<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\TokenHelper;
use function array_key_exists;
use function array_merge;
use function in_array;
use const T_CLOSE_PARENTHESIS;
use const T_COMMA;
use const T_ISSET;
use const T_OPEN_PARENTHESIS;
use const T_PARENT;
use const T_SELF;
use const T_STATIC;
use const T_UNSET;
use const T_VARIABLE;

class DisallowTrailingCommaInCallSniff implements Sniff
{

	public const CODE_DISALLOWED_TRAILING_COMMA = 'DisallowedTrailingComma';

	/** @var bool */
	public $onlySingleLine = false;

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [
			T_OPEN_PARENTHESIS,
		];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $parenthesisOpenerPointer
	 */
	public function process(File $phpcsFile, $parenthesisOpenerPointer): void
	{
		$tokens = $phpcsFile->getTokens();

		if (array_key_exists('parenthesis_owner', $tokens[$parenthesisOpenerPointer])) {
			return;
		}

		$pointerBeforeParenthesisOpener = TokenHelper::findPreviousEffective($phpcsFile, $parenthesisOpenerPointer - 1);
		if (!in_array(
			$tokens[$pointerBeforeParenthesisOpener]['code'],
			array_merge(
				TokenHelper::getOnlyNameTokenCodes(),
				[T_VARIABLE, T_ISSET, T_UNSET, T_CLOSE_PARENTHESIS, T_SELF, T_STATIC, T_PARENT]
			),
			true
		)) {
			return;
		}

		$parenthesisCloserPointer = $tokens[$parenthesisOpenerPointer]['parenthesis_closer'];
		$pointerBeforeParenthesisCloser = TokenHelper::findPreviousEffective($phpcsFile, $parenthesisCloserPointer - 1);

		if ($tokens[$pointerBeforeParenthesisCloser]['code'] !== T_COMMA) {
			return;
		}

		if ($this->onlySingleLine && $tokens[$parenthesisOpenerPointer]['line'] !== $tokens[$parenthesisCloserPointer]['line']) {
			return;
		}

		$fix = $phpcsFile->addFixableError(
			'Trailing comma after the last parameter in function call is disallowed.',
			$pointerBeforeParenthesisCloser,
			self::CODE_DISALLOWED_TRAILING_COMMA
		);

		if (!$fix) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->replaceToken($pointerBeforeParenthesisCloser, '');

		if ($tokens[$pointerBeforeParenthesisCloser]['line'] === $tokens[$parenthesisCloserPointer]['line']) {
			for ($i = $pointerBeforeParenthesisCloser + 1; $i < $parenthesisCloserPointer; $i++) {
				$phpcsFile->fixer->replaceToken($i, '');
			}
		}

		$phpcsFile->fixer->endChangeset();
	}

}
