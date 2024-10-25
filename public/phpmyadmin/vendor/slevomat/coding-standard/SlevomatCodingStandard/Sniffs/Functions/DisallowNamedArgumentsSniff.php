<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use function sprintf;
use const T_PARAM_NAME;

class DisallowNamedArgumentsSniff implements Sniff
{

	public const CODE_DISALLOWED_NAMED_ARGUMENT = 'DisallowedNamedArgument';

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [
			T_PARAM_NAME,
		];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $argumentNamePointer
	 */
	public function process(File $phpcsFile, $argumentNamePointer): void
	{
		$tokens = $phpcsFile->getTokens();

		$phpcsFile->addError(
			sprintf('Named arguments are disallowed, usage of named argument "%s" found.', $tokens[$argumentNamePointer]['content']),
			$argumentNamePointer,
			self::CODE_DISALLOWED_NAMED_ARGUMENT
		);
	}

}
