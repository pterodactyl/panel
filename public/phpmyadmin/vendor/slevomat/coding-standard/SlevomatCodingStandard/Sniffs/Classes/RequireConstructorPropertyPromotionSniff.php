<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\Annotation\VariableAnnotation;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\PropertyHelper;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\TypeHint;
use function array_filter;
use function array_reverse;
use function count;
use function in_array;
use function sprintf;
use function strtolower;
use function substr;
use function trim;
use const T_ATTRIBUTE_END;
use const T_BITWISE_AND;
use const T_CALLABLE;
use const T_CLOSE_CURLY_BRACKET;
use const T_COMMA;
use const T_DEC;
use const T_ELLIPSIS;
use const T_ELSE;
use const T_ELSEIF;
use const T_EQUAL;
use const T_FUNCTION;
use const T_IF;
use const T_INC;
use const T_OBJECT_OPERATOR;
use const T_OPEN_CURLY_BRACKET;
use const T_OPEN_PARENTHESIS;
use const T_SEMICOLON;
use const T_SWITCH;
use const T_VARIABLE;
use const T_WHITESPACE;

class RequireConstructorPropertyPromotionSniff implements Sniff
{

	public const CODE_REQUIRED_CONSTRUCTOR_PROPERTY_PROMOTION = 'RequiredConstructorPropertyPromotion';

	/** @var bool|null */
	public $enable = null;

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
		$this->enable = SniffSettingsHelper::isEnabledByPhpVersion($this->enable, 80000);

		if (!$this->enable) {
			return;
		}

		$tokens = $phpcsFile->getTokens();

		$namePointer = TokenHelper::findNextEffective($phpcsFile, $functionPointer + 1);

		if (strtolower($tokens[$namePointer]['content']) !== '__construct') {
			return;
		}

		if (FunctionHelper::isAbstract($phpcsFile, $functionPointer)) {
			return;
		}

		$parameterPointers = $this->getParameterPointers($phpcsFile, $functionPointer);

		if (count($parameterPointers) === 0) {
			return;
		}

		$parameterWithoutPromotionPointers = [];
		foreach ($parameterPointers as $parameterPointer) {
			$pointerBefore = TokenHelper::findPrevious($phpcsFile, [T_COMMA, T_OPEN_PARENTHESIS], $parameterPointer - 1);

			$visibilityPointer = TokenHelper::findNextEffective($phpcsFile, $pointerBefore + 1);

			if (in_array($tokens[$visibilityPointer]['code'], Tokens::$scopeModifiers, true)) {
				continue;
			}

			$pointerBefore = TokenHelper::findPreviousEffective($phpcsFile, $parameterPointer - 1);

			if ($tokens[$pointerBefore]['code'] === T_ELLIPSIS) {
				continue;
			}

			if ($tokens[$pointerBefore]['code'] === T_BITWISE_AND) {
				$pointerBefore = TokenHelper::findPreviousEffective($phpcsFile, $pointerBefore - 1);
			}

			if ($tokens[$pointerBefore]['code'] === T_CALLABLE) {
				continue;
			}

			$parameterWithoutPromotionPointers[] = $parameterPointer;
		}

		if (count($parameterWithoutPromotionPointers) === 0) {
			return;
		}

		/** @var int $classPointer */
		$classPointer = FunctionHelper::findClassPointer($phpcsFile, $functionPointer);

		$propertyPointers = $this->getPropertyPointers($phpcsFile, $classPointer);

		if (count($propertyPointers) === 0) {
			return;
		}

		foreach ($parameterWithoutPromotionPointers as $parameterPointer) {
			$parameterName = $tokens[$parameterPointer]['content'];

			foreach ($propertyPointers as $propertyPointer) {
				$propertyName = $tokens[$propertyPointer]['content'];

				if ($parameterName !== $propertyName) {
					continue;
				}

				if ($this->isPropertyDocCommentUseful($phpcsFile, $propertyPointer)) {
					continue;
				}

				if ($this->isPropertyWithAttribute($phpcsFile, $propertyPointer)) {
					continue;
				}

				$propertyTypeHint = PropertyHelper::findTypeHint($phpcsFile, $propertyPointer);
				$parameterTypeHint = FunctionHelper::getParametersTypeHints($phpcsFile, $functionPointer)[$parameterName];
				if (!$this->areTypeHintEqual($parameterTypeHint, $propertyTypeHint)) {
					continue;
				}

				$assignmentPointer = $this->getAssignment($phpcsFile, $functionPointer, $parameterName);
				if ($assignmentPointer === null) {
					continue;
				}

				if ($this->isParameterModifiedBeforeAssigment($phpcsFile, $functionPointer, $parameterName, $assignmentPointer)) {
					continue;
				}

				$fix = $phpcsFile->addFixableError(
					sprintf('Required promotion of property %s.', $propertyName),
					$propertyPointer,
					self::CODE_REQUIRED_CONSTRUCTOR_PROPERTY_PROMOTION
				);

				if (!$fix) {
					continue;
				}

				$visibilityPointer = TokenHelper::findPrevious($phpcsFile, Tokens::$scopeModifiers, $propertyPointer - 1);
				$visibility = $tokens[$visibilityPointer]['content'];

				$propertyDocCommentOpenerPointer = DocCommentHelper::findDocCommentOpenPointer($phpcsFile, $propertyPointer);
				$pointerBeforeProperty = TokenHelper::findFirstTokenOnLine(
					$phpcsFile,
					$propertyDocCommentOpenerPointer ?? $propertyPointer
				);
				$propertyEndPointer = TokenHelper::findNext($phpcsFile, T_SEMICOLON, $propertyPointer + 1);

				$propertyEqualPointer = TokenHelper::findNext($phpcsFile, T_EQUAL, $propertyPointer + 1, $propertyEndPointer);
				$propertyDefaultValue = $propertyEqualPointer !== null
					? trim(TokenHelper::getContent($phpcsFile, $propertyEqualPointer + 1, $propertyEndPointer - 1))
					: null;

				$propertyEndPointer = TokenHelper::findNext($phpcsFile, T_SEMICOLON, $propertyPointer + 1);
				$pointerAfterProperty = TokenHelper::findFirstTokenOnLine(
					$phpcsFile,
					TokenHelper::findNextExcluding($phpcsFile, T_WHITESPACE, $propertyEndPointer + 1)
				);

				$pointerBeforeParameterStart = TokenHelper::findPrevious($phpcsFile, [T_COMMA, T_OPEN_PARENTHESIS], $parameterPointer - 1);
				$parameterStartPointer = TokenHelper::findNextEffective($phpcsFile, $pointerBeforeParameterStart + 1);

				$parameterEqualPointer = TokenHelper::findNextEffective($phpcsFile, $parameterPointer + 1);
				$parameterHasDefaultValue = $tokens[$parameterEqualPointer]['code'] === T_EQUAL;

				$pointerBeforeAssignment = TokenHelper::findFirstTokenOnLine($phpcsFile, $assignmentPointer - 1);
				$pointerAfterAssignment = TokenHelper::findLastTokenOnLine($phpcsFile, $assignmentPointer);

				$phpcsFile->fixer->beginChangeset();

				for ($i = $pointerBeforeProperty; $i < $pointerAfterProperty; $i++) {
					$phpcsFile->fixer->replaceToken($i, '');
				}

				$phpcsFile->fixer->addContentBefore($parameterStartPointer, sprintf('%s ', $visibility));

				if (!$parameterHasDefaultValue && $propertyDefaultValue !== null) {
					$phpcsFile->fixer->addContent($parameterPointer, sprintf(' = %s', $propertyDefaultValue));
				}

				for ($i = $pointerBeforeAssignment; $i <= $pointerAfterAssignment; $i++) {
					$phpcsFile->fixer->replaceToken($i, '');
				}

				$phpcsFile->fixer->endChangeset();
			}
		}
	}

	private function getAssignment(File $phpcsFile, int $constructorPointer, string $parameterName): ?int
	{
		$tokens = $phpcsFile->getTokens();

		$parameterNameWithoutDollar = substr($parameterName, 1);

		for ($i = $tokens[$constructorPointer]['scope_opener'] + 1; $i < $tokens[$constructorPointer]['scope_closer']; $i++) {
			if ($tokens[$i]['content'] !== '$this') {
				continue;
			}

			$objectOperatorPointer = TokenHelper::findNextEffective($phpcsFile, $i + 1);
			if ($tokens[$objectOperatorPointer]['code'] !== T_OBJECT_OPERATOR) {
				continue;
			}

			$namePointer = TokenHelper::findNextEffective($phpcsFile, $objectOperatorPointer + 1);
			if ($tokens[$namePointer]['content'] !== $parameterNameWithoutDollar) {
				continue;
			}

			$equalPointer = TokenHelper::findNextEffective($phpcsFile, $namePointer + 1);
			if ($tokens[$equalPointer]['code'] !== T_EQUAL) {
				continue;
			}

			$variablePointer = TokenHelper::findNextEffective($phpcsFile, $equalPointer + 1);
			if ($tokens[$variablePointer]['content'] !== $parameterName) {
				continue;
			}

			$semicolonPointer = TokenHelper::findNextEffective($phpcsFile, $variablePointer + 1);
			if ($tokens[$semicolonPointer]['code'] !== T_SEMICOLON) {
				continue;
			}

			foreach (array_reverse($tokens[$semicolonPointer]['conditions']) as $conditionTokenCode) {
				if (in_array($conditionTokenCode, [T_IF, T_ELSEIF, T_ELSE, T_SWITCH], true)) {
					return null;
				}
			}

			return $i;
		}

		return null;
	}

	/**
	 * @return int[]
	 */
	private function getParameterPointers(File $phpcsFile, int $functionPointer): array
	{
		$tokens = $phpcsFile->getTokens();
		return TokenHelper::findNextAll(
			$phpcsFile,
			T_VARIABLE,
			$tokens[$functionPointer]['parenthesis_opener'] + 1,
			$tokens[$functionPointer]['parenthesis_closer']
		);
	}

	/**
	 * @return int[]
	 */
	private function getPropertyPointers(File $phpcsFile, int $classPointer): array
	{
		$tokens = $phpcsFile->getTokens();

		return array_filter(
			TokenHelper::findNextAll(
				$phpcsFile,
				T_VARIABLE,
				$tokens[$classPointer]['scope_opener'] + 1,
				$tokens[$classPointer]['scope_closer']
			),
			static function (int $variablePointer) use ($phpcsFile): bool {
				return PropertyHelper::isProperty($phpcsFile, $variablePointer);
			}
		);
	}

	private function isPropertyDocCommentUseful(File $phpcsFile, int $propertyPointer): bool
	{
		if (DocCommentHelper::hasDocCommentDescription($phpcsFile, $propertyPointer)) {
			return true;
		}

		foreach (AnnotationHelper::getAnnotations($phpcsFile, $propertyPointer) as $annotationType => $annotations) {
			if ($annotationType !== '@var') {
				return true;
			}

			/** @var VariableAnnotation $annotation */
			foreach ($annotations as $annotation) {
				if ($annotation->hasDescription()) {
					return true;
				}
			}
		}

		return false;
	}

	private function isPropertyWithAttribute(File $phpcsFile, int $propertyPointer): bool
	{
		$tokens = $phpcsFile->getTokens();

		$previousPointer = TokenHelper::findPrevious(
			$phpcsFile,
			[T_ATTRIBUTE_END, T_SEMICOLON, T_OPEN_CURLY_BRACKET, T_CLOSE_CURLY_BRACKET],
			$propertyPointer - 1
		);

		return $tokens[$previousPointer]['code'] === T_ATTRIBUTE_END;
	}

	private function areTypeHintEqual(?TypeHint $parameterTypeHint, ?TypeHint $propertyTypeHint): bool
	{
		if ($parameterTypeHint === null && $propertyTypeHint === null) {
			return true;
		}

		if ($parameterTypeHint === null || $propertyTypeHint === null) {
			return false;
		}

		return $parameterTypeHint->getTypeHint() === $propertyTypeHint->getTypeHint();
	}

	private function isParameterModifiedBeforeAssigment(
		File $phpcsFile,
		int $functionPointer,
		string $parameterName,
		int $assigmentPointer
	): bool
	{
		$tokens = $phpcsFile->getTokens();

		for ($i = $assigmentPointer - 1; $i > $tokens[$functionPointer]['scope_opener']; $i--) {
			if ($tokens[$i]['code'] !== T_VARIABLE) {
				continue;
			}

			if ($tokens[$i]['content'] !== $parameterName) {
				continue;
			}

			$nextPointer = TokenHelper::findNextEffective($phpcsFile, $i + 1);
			if (in_array($tokens[$nextPointer]['code'], Tokens::$assignmentTokens, true)) {
				return true;
			}

			if ($tokens[$nextPointer]['code'] === T_INC) {
				return true;
			}

			$previousPointer = TokenHelper::findNextEffective($phpcsFile, $i - 1);
			if ($tokens[$previousPointer]['code'] === T_DEC) {
				return true;
			}
		}

		return false;
	}

}
