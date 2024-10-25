<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\TokenHelper;
use function sprintf;
use function strtolower;
use const T_FUNCTION;
use const T_VARIABLE;

class DisallowConstructorPropertyPromotionSniff implements Sniff
{

	public const CODE_DISALLOWED_CONSTRUCTOR_PROPERTY_PROMOTION = 'DisallowedConstructorPropertyPromotion';

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [T_FUNCTION];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $functionPointer
	 */
	public function process(File $phpcsFile, $functionPointer): void
	{
		$tokens = $phpcsFile->getTokens();

		$namePointer = TokenHelper::findNextEffective($phpcsFile, $functionPointer + 1);

		if (strtolower($tokens[$namePointer]['content']) !== '__construct') {
			return;
		}

		$visibilityPointers = TokenHelper::findNextAll(
			$phpcsFile,
			Tokens::$scopeModifiers,
			$tokens[$functionPointer]['parenthesis_opener'] + 1,
			$tokens[$functionPointer]['parenthesis_closer']
		);

		if ($visibilityPointers === []) {
			return;
		}

		foreach ($visibilityPointers as $visibilityPointer) {
			$variablePointer = TokenHelper::findNext($phpcsFile, T_VARIABLE, $visibilityPointer + 1);

			$phpcsFile->addError(
				sprintf(
					'Constructor property promotion is disallowed, promotion of property %s found.',
					$tokens[$variablePointer]['content']
				),
				$variablePointer,
				self::CODE_DISALLOWED_CONSTRUCTOR_PROPERTY_PROMOTION
			);
		}
	}

}
