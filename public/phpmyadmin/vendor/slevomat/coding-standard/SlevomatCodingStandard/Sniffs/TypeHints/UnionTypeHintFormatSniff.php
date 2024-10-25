<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\PropertyHelper;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\TypeHint;
use function array_merge;
use function explode;
use function implode;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;
use function substr_count;
use const T_TYPE_UNION;
use const T_VARIABLE;
use const T_WHITESPACE;

class UnionTypeHintFormatSniff implements Sniff
{

	public const CODE_DISALLOWED_WHITESPACE = 'DisallowedWhitespace';
	public const CODE_REQUIRED_WHITESPACE = 'RequiredWhitespace';
	public const CODE_REQUIRED_SHORT_NULLABLE = 'RequiredShortNullable';
	public const CODE_DISALLOWED_SHORT_NULLABLE = 'DisallowedShortNullable';
	public const CODE_NULL_TYPE_HINT_NOT_ON_FIRST_POSITION = 'NullTypeHintNotOnFirstPosition';
	public const CODE_NULL_TYPE_HINT_NOT_ON_LAST_POSITION = 'NullTypeHintNotOnLastPosition';

	private const YES = 'yes';
	private const NO = 'no';

	private const FIRST = 'first';
	private const LAST = 'last';

	/** @var bool|null */
	public $enable = null;

	/** @var string|null */
	public $withSpaces = null;

	/** @var string|null */
	public $shortNullable = null;

	/** @var string|null */
	public $nullPosition = null;

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return array_merge(
			[T_VARIABLE],
			TokenHelper::$functionTokenCodes
		);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $pointer
	 */
	public function process(File $phpcsFile, $pointer): void
	{
		$this->enable = SniffSettingsHelper::isEnabledByPhpVersion($this->enable, 80000);

		if (!$this->enable) {
			return;
		}

		$tokens = $phpcsFile->getTokens();

		if ($tokens[$pointer]['code'] === T_VARIABLE) {
			if (!PropertyHelper::isProperty($phpcsFile, $pointer)) {
				return;
			}

			$propertyTypeHint = PropertyHelper::findTypeHint($phpcsFile, $pointer);
			if ($propertyTypeHint !== null) {
				$this->checkTypeHint($phpcsFile, $propertyTypeHint);
			}

			return;
		}

		$returnTypeHint = FunctionHelper::findReturnTypeHint($phpcsFile, $pointer);
		if ($returnTypeHint !== null) {
			$this->checkTypeHint($phpcsFile, $returnTypeHint);
		}

		foreach (FunctionHelper::getParametersTypeHints($phpcsFile, $pointer) as $parameterTypeHint) {
			if ($parameterTypeHint !== null) {
				$this->checkTypeHint($phpcsFile, $parameterTypeHint);
			}
		}
	}

	private function checkTypeHint(File $phpcsFile, TypeHint $typeHint): void
	{
		$tokens = $phpcsFile->getTokens();

		$typeHintsCount = substr_count($typeHint->getTypeHint(), '|') + 1;

		if ($typeHintsCount > 1) {
			if ($this->withSpaces === self::NO) {
				$whitespacePointer = TokenHelper::findNext(
					$phpcsFile,
					T_WHITESPACE,
					$typeHint->getStartPointer() + 1,
					$typeHint->getEndPointer()
				);
				if ($whitespacePointer !== null) {
					$originalTypeHint = TokenHelper::getContent($phpcsFile, $typeHint->getStartPointer(), $typeHint->getEndPointer());
					$fix = $phpcsFile->addFixableError(
						sprintf('Spaces in type hint "%s" are disallowed.', $originalTypeHint),
						$typeHint->getStartPointer(),
						self::CODE_DISALLOWED_WHITESPACE
					);
					if ($fix) {
						$this->fixTypeHint($phpcsFile, $typeHint, $typeHint->getTypeHint());
					}
				}
			} elseif ($this->withSpaces === self::YES) {
				$error = false;
				foreach (TokenHelper::findNextAll(
					$phpcsFile,
					[T_TYPE_UNION],
					$typeHint->getStartPointer(),
					$typeHint->getEndPointer()
				) as $unionSeparator) {
					if ($tokens[$unionSeparator - 1]['content'] !== ' ') {
						$error = true;
						break;
					}
					if ($tokens[$unionSeparator + 1]['content'] !== ' ') {
						$error = true;
						break;
					}
				}

				if ($error) {
					$originalTypeHint = TokenHelper::getContent($phpcsFile, $typeHint->getStartPointer(), $typeHint->getEndPointer());
					$fix = $phpcsFile->addFixableError(
						sprintf('One space required before and after each "|" in type hint "%s".', $originalTypeHint),
						$typeHint->getStartPointer(),
						self::CODE_REQUIRED_WHITESPACE
					);
					if ($fix) {
						$fixedTypeHint = implode(' | ', explode('|', $typeHint->getTypeHint()));
						$this->fixTypeHint($phpcsFile, $typeHint, $fixedTypeHint);
					}
				}
			}
		}

		if (!$typeHint->isNullable()) {
			return;
		}

		$hasShortNullable = strpos($typeHint->getTypeHint(), '?') === 0;

		if ($this->shortNullable === self::YES && $typeHintsCount === 2 && !$hasShortNullable) {
			$fix = $phpcsFile->addFixableError(
				sprintf('Short nullable type hint in "%s" is required.', $typeHint->getTypeHint()),
				$typeHint->getStartPointer(),
				self::CODE_REQUIRED_SHORT_NULLABLE
			);
			if ($fix) {
				$typeHintWithoutNull = self::getTypeHintContentWithoutNull($phpcsFile, $typeHint);
				$this->fixTypeHint($phpcsFile, $typeHint, '?' . $typeHintWithoutNull);
			}
		} elseif ($this->shortNullable === self::NO && $hasShortNullable) {
			$fix = $phpcsFile->addFixableError(
				sprintf('Usage of short nullable type hint in "%s" is disallowed.', $typeHint->getTypeHint()),
				$typeHint->getStartPointer(),
				self::CODE_DISALLOWED_SHORT_NULLABLE
			);
			if ($fix) {
				$this->fixTypeHint($phpcsFile, $typeHint, substr($typeHint->getTypeHint(), 1) . '|null');
			}
		}

		if ($hasShortNullable || ($this->shortNullable === self::YES && $typeHintsCount === 2)) {
			return;
		}

		if ($this->nullPosition === self::FIRST && strtolower($tokens[$typeHint->getStartPointer()]['content']) !== 'null') {
			$fix = $phpcsFile->addFixableError(
				sprintf('Null type hint should be on first position in "%s".', $typeHint->getTypeHint()),
				$typeHint->getStartPointer(),
				self::CODE_NULL_TYPE_HINT_NOT_ON_FIRST_POSITION
			);
			if ($fix) {
				$this->fixTypeHint($phpcsFile, $typeHint, 'null|' . self::getTypeHintContentWithoutNull($phpcsFile, $typeHint));
			}
		} elseif ($this->nullPosition === self::LAST && strtolower($tokens[$typeHint->getEndPointer()]['content']) !== 'null') {
			$fix = $phpcsFile->addFixableError(
				sprintf('Null type hint should be on last position in "%s".', $typeHint->getTypeHint()),
				$typeHint->getStartPointer(),
				self::CODE_NULL_TYPE_HINT_NOT_ON_LAST_POSITION
			);
			if ($fix) {
				$this->fixTypeHint($phpcsFile, $typeHint, self::getTypeHintContentWithoutNull($phpcsFile, $typeHint) . '|null');
			}
		}
	}

	private function getTypeHintContentWithoutNull(File $phpcsFile, TypeHint $typeHint): string
	{
		$tokens = $phpcsFile->getTokens();

		if (strtolower($tokens[$typeHint->getEndPointer()]['content']) === 'null') {
			$previousTypeHintPointer = TokenHelper::findPrevious(
				$phpcsFile,
				TokenHelper::getOnlyTypeHintTokenCodes(),
				$typeHint->getEndPointer() - 1
			);
			return TokenHelper::getContent($phpcsFile, $typeHint->getStartPointer(), $previousTypeHintPointer);
		}

		$content = '';

		for ($i = $typeHint->getStartPointer(); $i <= $typeHint->getEndPointer(); $i++) {
			if (strtolower($tokens[$i]['content']) === 'null') {
				$i = TokenHelper::findNext($phpcsFile, TokenHelper::getOnlyTypeHintTokenCodes(), $i + 1);
			}

			$content .= $tokens[$i]['content'];
		}

		return $content;
	}

	private function fixTypeHint(File $phpcsFile, TypeHint $typeHint, string $fixedTypeHint): void
	{
		$phpcsFile->fixer->beginChangeset();

		$phpcsFile->fixer->replaceToken($typeHint->getStartPointer(), $fixedTypeHint);
		for ($i = $typeHint->getStartPointer() + 1; $i <= $typeHint->getEndPointer(); $i++) {
			$phpcsFile->fixer->replaceToken($i, '');
		}

		$phpcsFile->fixer->endChangeset();
	}

}
