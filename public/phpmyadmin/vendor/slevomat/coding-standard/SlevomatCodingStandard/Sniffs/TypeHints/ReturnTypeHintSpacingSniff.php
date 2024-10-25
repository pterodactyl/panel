<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use function sprintf;
use function str_repeat;
use const T_CLOSE_PARENTHESIS;
use const T_NULLABLE;
use const T_WHITESPACE;

class ReturnTypeHintSpacingSniff implements Sniff
{

	public const CODE_NO_SPACE_BETWEEN_COLON_AND_TYPE_HINT = 'NoSpaceBetweenColonAndTypeHint';

	public const CODE_MULTIPLE_SPACES_BETWEEN_COLON_AND_TYPE_HINT = 'MultipleSpacesBetweenColonAndTypeHint';

	public const CODE_NO_SPACE_BETWEEN_COLON_AND_NULLABILITY_SYMBOL = 'NoSpaceBetweenColonAndNullabilitySymbol';

	public const CODE_MULTIPLE_SPACES_BETWEEN_COLON_AND_NULLABILITY_SYMBOL = 'MultipleSpacesBetweenColonAndNullabilitySymbol';

	public const CODE_WHITESPACE_BEFORE_COLON = 'WhitespaceBeforeColon';

	public const CODE_INCORRECT_SPACES_BEFORE_COLON = 'IncorrectWhitespaceBeforeColon';

	public const CODE_WHITESPACE_AFTER_NULLABILITY_SYMBOL = 'WhitespaceAfterNullabilitySymbol';

	/** @var int */
	public $spacesCountBeforeColon = 0;

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
		$typeHint = FunctionHelper::findReturnTypeHint($phpcsFile, $functionPointer);

		if ($typeHint === null) {
			return;
		}

		$tokens = $phpcsFile->getTokens();

		$typeHintStartPointer = $typeHint->getStartPointer();

		/** @var int $colonPointer */
		$colonPointer = TokenHelper::findPreviousEffective($phpcsFile, $typeHintStartPointer - 1);

		if ($tokens[$typeHintStartPointer]['code'] !== T_NULLABLE) {
			if ($tokens[$colonPointer + 1]['code'] !== T_WHITESPACE) {
				$fix = $phpcsFile->addFixableError(
					'There must be exactly one space between return type hint colon and return type hint.',
					$typeHintStartPointer,
					self::CODE_NO_SPACE_BETWEEN_COLON_AND_TYPE_HINT
				);
				if ($fix) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->addContent($colonPointer, ' ');
					$phpcsFile->fixer->endChangeset();
				}
			} elseif ($tokens[$colonPointer + 1]['content'] !== ' ') {
				$fix = $phpcsFile->addFixableError(
					'There must be exactly one space between return type hint colon and return type hint.',
					$typeHintStartPointer,
					self::CODE_MULTIPLE_SPACES_BETWEEN_COLON_AND_TYPE_HINT
				);
				if ($fix) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken($colonPointer + 1, ' ');
					$phpcsFile->fixer->endChangeset();
				}
			}
		} else {
			if ($tokens[$colonPointer + 1]['code'] !== T_WHITESPACE) {
				$fix = $phpcsFile->addFixableError(
					'There must be exactly one space between return type hint colon and return type hint nullability symbol.',
					$typeHintStartPointer,
					self::CODE_NO_SPACE_BETWEEN_COLON_AND_NULLABILITY_SYMBOL
				);
				if ($fix) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->addContent($colonPointer, ' ');
					$phpcsFile->fixer->endChangeset();
				}
			} elseif ($tokens[$colonPointer + 1]['content'] !== ' ') {
				$fix = $phpcsFile->addFixableError(
					'There must be exactly one space between return type hint colon and return type hint nullability symbol.',
					$typeHintStartPointer,
					self::CODE_MULTIPLE_SPACES_BETWEEN_COLON_AND_NULLABILITY_SYMBOL
				);
				if ($fix) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken($colonPointer + 1, ' ');
					$phpcsFile->fixer->endChangeset();
				}
			}

			if ($tokens[$typeHintStartPointer + 1]['code'] === T_WHITESPACE) {
				$fix = $phpcsFile->addFixableError(
					'There must be no whitespace between return type hint nullability symbol and return type hint.',
					$typeHintStartPointer,
					self::CODE_WHITESPACE_AFTER_NULLABILITY_SYMBOL
				);
				if ($fix) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken($typeHintStartPointer + 1, '');
					$phpcsFile->fixer->endChangeset();
				}
			}
		}

		$spacesCountBeforeColon = SniffSettingsHelper::normalizeInteger($this->spacesCountBeforeColon);
		$expectedSpaces = str_repeat(' ', $spacesCountBeforeColon);

		if (
			$tokens[$colonPointer - 1]['code'] !== T_CLOSE_PARENTHESIS
			&& $tokens[$colonPointer - 1]['content'] !== $expectedSpaces
		) {
			$fix = $spacesCountBeforeColon === 0
				? $phpcsFile->addFixableError(
					'There must be no whitespace between closing parenthesis and return type colon.',
					$typeHintStartPointer,
					self::CODE_WHITESPACE_BEFORE_COLON
				)
				: $phpcsFile->addFixableError(
					sprintf(
						'There must be exactly %d whitespace%s between closing parenthesis and return type colon.',
						$spacesCountBeforeColon,
						$spacesCountBeforeColon !== 1 ? 's' : ''
					),
					$typeHintStartPointer,
					self::CODE_INCORRECT_SPACES_BEFORE_COLON
				);
			if ($fix) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->replaceToken($colonPointer - 1, $expectedSpaces);
				$phpcsFile->fixer->endChangeset();
			}
		} elseif ($tokens[$colonPointer - 1]['code'] === T_CLOSE_PARENTHESIS && $spacesCountBeforeColon !== 0) {
			$fix = $phpcsFile->addFixableError(
				sprintf(
					'There must be exactly %d whitespace%s between closing parenthesis and return type colon.',
					$spacesCountBeforeColon,
					$spacesCountBeforeColon !== 1 ? 's' : ''
				),
				$typeHintStartPointer,
				self::CODE_INCORRECT_SPACES_BEFORE_COLON
			);
			if ($fix) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->addContent($colonPointer - 1, $expectedSpaces);
				$phpcsFile->fixer->endChangeset();
			}
		}
	}

}
