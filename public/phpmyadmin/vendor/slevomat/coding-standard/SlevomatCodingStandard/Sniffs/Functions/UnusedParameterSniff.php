<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\SuppressHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\VariableHelper;
use function array_merge;
use function in_array;
use function sprintf;
use const T_COMMA;
use const T_VARIABLE;

class UnusedParameterSniff implements Sniff
{

	public const CODE_UNUSED_PARAMETER = 'UnusedParameter';
	public const CODE_USELESS_SUPPRESS = 'UselessSuppress';

	private const NAME = 'SlevomatCodingStandard.Functions.UnusedParameter';

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
		if (FunctionHelper::isAbstract($phpcsFile, $functionPointer)) {
			return;
		}

		$isSuppressed = SuppressHelper::isSniffSuppressed($phpcsFile, $functionPointer, $this->getSniffName(self::CODE_UNUSED_PARAMETER));
		$suppressUseless = true;

		$tokens = $phpcsFile->getTokens();

		$currentPointer = $tokens[$functionPointer]['parenthesis_opener'] + 1;
		while (true) {
			$parameterPointer = TokenHelper::findNext(
				$phpcsFile,
				T_VARIABLE,
				$currentPointer,
				$tokens[$functionPointer]['parenthesis_closer']
			);
			if ($parameterPointer === null) {
				break;
			}

			$previousPointer = TokenHelper::findPrevious(
				$phpcsFile,
				array_merge([T_COMMA], Tokens::$scopeModifiers),
				$parameterPointer - 1,
				$tokens[$functionPointer]['parenthesis_opener']
			);

			if ($previousPointer !== null && in_array($tokens[$previousPointer]['code'], Tokens::$scopeModifiers, true)) {
				$currentPointer = $parameterPointer + 1;
				continue;
			}

			if (VariableHelper::isUsedInScope($phpcsFile, $functionPointer, $parameterPointer)) {
				$currentPointer = $parameterPointer + 1;
				continue;
			}

			if (!$isSuppressed) {
				$phpcsFile->addError(
					sprintf('Unused parameter %s.', $tokens[$parameterPointer]['content']),
					$parameterPointer,
					self::CODE_UNUSED_PARAMETER
				);
			} else {
				$suppressUseless = false;
			}

			$currentPointer = $parameterPointer + 1;
		}

		if (!$isSuppressed || !$suppressUseless) {
			return;
		}

		$phpcsFile->addError(
			sprintf('Useless %s %s', SuppressHelper::ANNOTATION, self::NAME),
			$functionPointer,
			self::CODE_USELESS_SUPPRESS
		);
	}

	private function getSniffName(string $sniffName): string
	{
		return sprintf('%s.%s', self::NAME, $sniffName);
	}

}
