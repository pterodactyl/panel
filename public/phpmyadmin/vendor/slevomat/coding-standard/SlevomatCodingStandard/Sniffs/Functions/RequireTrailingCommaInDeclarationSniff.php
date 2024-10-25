<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use const T_COMMA;

class RequireTrailingCommaInDeclarationSniff implements Sniff
{

	public const CODE_MISSING_TRAILING_COMMA = 'MissingTrailingComma';

	/** @var bool|null */
	public $enable = null;

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return TokenHelper::$functionTokenCodes;
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

		$parenthesisOpenerPointer = $tokens[$functionPointer]['parenthesis_opener'];
		$parenthesisCloserPointer = $tokens[$functionPointer]['parenthesis_closer'];

		if ($tokens[$parenthesisOpenerPointer]['line'] === $tokens[$parenthesisCloserPointer]['line']) {
			return;
		}

		$pointerBeforeParenthesisCloser = TokenHelper::findPreviousEffective(
			$phpcsFile,
			$parenthesisCloserPointer - 1,
			$parenthesisOpenerPointer
		);

		if ($pointerBeforeParenthesisCloser === $parenthesisOpenerPointer) {
			return;
		}

		if ($tokens[$pointerBeforeParenthesisCloser]['code'] === T_COMMA) {
			return;
		}

		$fix = $phpcsFile->addFixableError(
			'Multi-line function declaration must have a trailing comma after the last parameter.',
			$pointerBeforeParenthesisCloser,
			self::CODE_MISSING_TRAILING_COMMA
		);

		if (!$fix) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->addContent($pointerBeforeParenthesisCloser, ',');
		$phpcsFile->fixer->endChangeset();
	}

}
