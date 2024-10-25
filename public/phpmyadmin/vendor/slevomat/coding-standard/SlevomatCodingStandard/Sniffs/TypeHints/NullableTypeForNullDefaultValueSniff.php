<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\SuppressHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\TypeHintHelper;
use function array_merge;
use function in_array;
use function preg_match;
use function sprintf;
use function strtolower;
use function substr_count;
use const T_BITWISE_AND;
use const T_ELLIPSIS;
use const T_EQUAL;
use const T_NULL;
use const T_NULLABLE;
use const T_VARIABLE;

class NullableTypeForNullDefaultValueSniff implements Sniff
{

	public const CODE_NULLABILITY_TYPE_MISSING = 'NullabilityTypeMissing';

	private const NAME = 'SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue';

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
		if (SuppressHelper::isSniffSuppressed($phpcsFile, $functionPointer, self::NAME)) {
			return;
		}

		$tokens = $phpcsFile->getTokens();
		$startPointer = $tokens[$functionPointer]['parenthesis_opener'] + 1;
		$endPointer = $tokens[$functionPointer]['parenthesis_closer'];

		$typeHintTokenCodes = TokenHelper::getOnlyTypeHintTokenCodes();

		for ($i = $startPointer; $i < $endPointer; $i++) {
			if ($tokens[$i]['code'] !== T_VARIABLE) {
				continue;
			}

			$parameterName = $tokens[$i]['content'];

			$afterVariablePointer = TokenHelper::findNextEffective($phpcsFile, $i + 1);
			if ($tokens[$afterVariablePointer]['code'] !== T_EQUAL) {
				continue;
			}

			$afterEqualsPointer = TokenHelper::findNextEffective($phpcsFile, $afterVariablePointer + 1);
			if ($tokens[$afterEqualsPointer]['code'] !== T_NULL) {
				continue;
			}

			$ignoreTokensToFindTypeHint = array_merge(TokenHelper::$ineffectiveTokenCodes, [T_BITWISE_AND, T_ELLIPSIS]);
			$typeHintEndPointer = TokenHelper::findPreviousExcluding($phpcsFile, $ignoreTokensToFindTypeHint, $i - 1, $startPointer);

			if (
				$typeHintEndPointer === null
				|| !in_array($tokens[$typeHintEndPointer]['code'], $typeHintTokenCodes, true)
			) {
				continue;
			}

			$typeHintStartPointer = TypeHintHelper::getStartPointer($phpcsFile, $typeHintEndPointer);

			$typeHint = TokenHelper::getContent($phpcsFile, $typeHintStartPointer, $typeHintEndPointer);

			if (strtolower($typeHint) === 'mixed') {
				continue;
			}

			$nullableSymbolPointer = TokenHelper::findPreviousEffective(
				$phpcsFile,
				$typeHintStartPointer - 1,
				$tokens[$functionPointer]['parenthesis_opener']
			);

			if ($nullableSymbolPointer !== null && $tokens[$nullableSymbolPointer]['code'] === T_NULLABLE) {
				continue;
			}

			if (preg_match('~(?:^|(?:\|\s*))null(?:(?:\s*\|)|$)~i', $typeHint) === 1) {
				continue;
			}

			$fix = $phpcsFile->addFixableError(
				sprintf('Parameter %s has null default value, but is not marked as nullable.', $parameterName),
				$i,
				self::CODE_NULLABILITY_TYPE_MISSING
			);

			if (!$fix) {
				continue;
			}

			$phpcsFile->fixer->beginChangeset();

			if (substr_count($typeHint, '|') > 0) {
				$phpcsFile->fixer->addContent($typeHintEndPointer, '|null');
			} else {
				$phpcsFile->fixer->addContentBefore($typeHintStartPointer, '?');
			}

			$phpcsFile->fixer->endChangeset();
		}
	}

}
