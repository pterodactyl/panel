<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use const T_CLOSURE;
use const T_COMMA;
use const T_USE;
use const T_WHITESPACE;

class RequireTrailingCommaInClosureUseSniff implements Sniff
{

	public const CODE_MISSING_TRAILING_COMMA = 'MissingTrailingComma';

	/** @var bool|null */
	public $enable = null;

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [T_CLOSURE];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $functionPointer
	 */
	public function process(File $phpcsFile, $functionPointer): void
	{
		$this->enable = SniffSettingsHelper::isEnabledByPhpVersion($this->enable, 80000);

		if (!$this->enable) {
			return;
		}

		$tokens = $phpcsFile->getTokens();

		$parenthesisCloserPointer = $tokens[$functionPointer]['parenthesis_closer'];

		$usePointer = TokenHelper::findNextEffective($phpcsFile, $parenthesisCloserPointer + 1);

		if ($tokens[$usePointer]['code'] !== T_USE) {
			return;
		}

		$useParenthesisOpenerPointer = TokenHelper::findNextEffective($phpcsFile, $usePointer + 1);
		$useParenthesisCloserPointer = $tokens[$useParenthesisOpenerPointer]['parenthesis_closer'];

		if ($tokens[$useParenthesisOpenerPointer]['line'] === $tokens[$useParenthesisCloserPointer]['line']) {
			return;
		}

		$pointerBeforeUseParenthesisCloser = TokenHelper::findPreviousExcluding(
			$phpcsFile,
			T_WHITESPACE,
			$useParenthesisCloserPointer - 1,
			$useParenthesisOpenerPointer
		);

		if ($tokens[$pointerBeforeUseParenthesisCloser]['code'] === T_COMMA) {
			return;
		}

		$fix = $phpcsFile->addFixableError(
			'Multi-line "use" of closure declaration must have a trailing comma after the last inherited variable.',
			$pointerBeforeUseParenthesisCloser,
			self::CODE_MISSING_TRAILING_COMMA
		);

		if (!$fix) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->addContent($pointerBeforeUseParenthesisCloser, ',');
		$phpcsFile->fixer->endChangeset();
	}

}
