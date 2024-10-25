<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Helpers;

use Generator;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\Annotation\ParameterAnnotation;
use SlevomatCodingStandard\Helpers\Annotation\ReturnAnnotation;
use function array_filter;
use function array_map;
use function array_merge;
use function array_pop;
use function array_reverse;
use function count;
use function in_array;
use function iterator_to_array;
use function preg_match;
use function preg_replace;
use function sprintf;
use function substr_count;
use const T_ANON_CLASS;
use const T_BITWISE_AND;
use const T_CLASS;
use const T_CLOSURE;
use const T_COLON;
use const T_ELLIPSIS;
use const T_FUNCTION;
use const T_INTERFACE;
use const T_NULLABLE;
use const T_RETURN;
use const T_SEMICOLON;
use const T_STRING;
use const T_TRAIT;
use const T_USE;
use const T_VARIABLE;
use const T_WHITESPACE;
use const T_YIELD;
use const T_YIELD_FROM;

/**
 * @internal
 */
class FunctionHelper
{

	public const LINE_INCLUDE_COMMENT = 1;
	public const LINE_INCLUDE_WHITESPACE = 2;

	public const SPECIAL_FUNCTIONS = [
		'array_key_exists',
		'array_slice',
		'boolval',
		'call_user_func',
		'call_user_func_array',
		'chr',
		'count',
		'doubleval',
		'defined',
		'floatval',
		'func_get_args',
		'func_num_args',
		'get_called_class',
		'get_class',
		'gettype',
		'in_array',
		'intval',
		'is_array',
		'is_bool',
		'is_double',
		'is_float',
		'is_long',
		'is_int',
		'is_integer',
		'is_null',
		'is_object',
		'is_real',
		'is_resource',
		'is_string',
		'ord',
		'sizeof',
		'strlen',
		'strval',
	];

	public static function getTypeLabel(File $phpcsFile, int $functionPointer): string
	{
		return self::isMethod($phpcsFile, $functionPointer) ? 'Method' : 'Function';
	}

	public static function getName(File $phpcsFile, int $functionPointer): string
	{
		$tokens = $phpcsFile->getTokens();
		return $tokens[TokenHelper::findNext(
			$phpcsFile,
			T_STRING,
			$functionPointer + 1,
			$tokens[$functionPointer]['parenthesis_opener']
		)]['content'];
	}

	public static function getFullyQualifiedName(File $phpcsFile, int $functionPointer): string
	{
		$name = self::getName($phpcsFile, $functionPointer);
		$namespace = NamespaceHelper::findCurrentNamespaceName($phpcsFile, $functionPointer);

		if (self::isMethod($phpcsFile, $functionPointer)) {
			foreach (array_reverse(
				$phpcsFile->getTokens()[$functionPointer]['conditions'],
				true
			) as $conditionPointer => $conditionTokenCode) {
				if ($conditionTokenCode === T_ANON_CLASS) {
					return sprintf('class@anonymous::%s', $name);
				}

				if (in_array($conditionTokenCode, [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
					$name = sprintf(
						'%s%s::%s',
						NamespaceHelper::NAMESPACE_SEPARATOR,
						ClassHelper::getName($phpcsFile, $conditionPointer),
						$name
					);
					break;
				}
			}

			return $namespace !== null ? sprintf('%s%s%s', NamespaceHelper::NAMESPACE_SEPARATOR, $namespace, $name) : $name;
		}

		return $namespace !== null
			? sprintf('%s%s%s%s', NamespaceHelper::NAMESPACE_SEPARATOR, $namespace, NamespaceHelper::NAMESPACE_SEPARATOR, $name)
			: $name;
	}

	public static function isAbstract(File $phpcsFile, int $functionPointer): bool
	{
		return !isset($phpcsFile->getTokens()[$functionPointer]['scope_opener']);
	}

	public static function isMethod(File $phpcsFile, int $functionPointer): bool
	{
		$functionPointerConditions = $phpcsFile->getTokens()[$functionPointer]['conditions'];
		if ($functionPointerConditions === []) {
			return false;
		}
		$lastFunctionPointerCondition = array_pop($functionPointerConditions);
		return in_array($lastFunctionPointerCondition, [T_CLASS, T_INTERFACE, T_TRAIT, T_ANON_CLASS], true);
	}

	public static function findClassPointer(File $phpcsFile, int $functionPointer): ?int
	{
		$tokens = $phpcsFile->getTokens();

		if ($tokens[$functionPointer]['code'] === T_CLOSURE) {
			return null;
		}

		foreach (array_reverse($tokens[$functionPointer]['conditions'], true) as $conditionPointer => $conditionTokenCode) {
			if (!in_array($conditionTokenCode, [T_CLASS, T_INTERFACE, T_TRAIT, T_ANON_CLASS], true)) {
				continue;
			}

			return $conditionPointer;
		}

		return null;
	}

	/**
	 * @return string[]
	 */
	public static function getParametersNames(File $phpcsFile, int $functionPointer): array
	{
		$tokens = $phpcsFile->getTokens();

		$parametersNames = [];
		for ($i = $tokens[$functionPointer]['parenthesis_opener'] + 1; $i < $tokens[$functionPointer]['parenthesis_closer']; $i++) {
			if ($tokens[$i]['code'] !== T_VARIABLE) {
				continue;
			}

			$parametersNames[] = $tokens[$i]['content'];
		}

		return $parametersNames;
	}

	/**
	 * @return (TypeHint|null)[]
	 */
	public static function getParametersTypeHints(File $phpcsFile, int $functionPointer): array
	{
		$tokens = $phpcsFile->getTokens();

		$parametersTypeHints = [];
		for ($i = $tokens[$functionPointer]['parenthesis_opener'] + 1; $i < $tokens[$functionPointer]['parenthesis_closer']; $i++) {
			if ($tokens[$i]['code'] !== T_VARIABLE) {
				continue;
			}

			$parameterName = $tokens[$i]['content'];

			$pointerBeforeVariable = TokenHelper::findPreviousExcluding(
				$phpcsFile,
				array_merge(TokenHelper::$ineffectiveTokenCodes, [T_BITWISE_AND, T_ELLIPSIS]),
				$i - 1
			);

			if (!in_array($tokens[$pointerBeforeVariable]['code'], TokenHelper::getTypeHintTokenCodes(), true)) {
				$parametersTypeHints[$parameterName] = null;
				continue;
			}

			$typeHintEndPointer = $pointerBeforeVariable;
			$typeHintStartPointer = TypeHintHelper::getStartPointer($phpcsFile, $typeHintEndPointer);

			$pointerBeforeTypeHint = TokenHelper::findPreviousEffective($phpcsFile, $typeHintStartPointer - 1);
			$isNullable = $tokens[$pointerBeforeTypeHint]['code'] === T_NULLABLE;
			if ($isNullable) {
				$typeHintStartPointer = $pointerBeforeTypeHint;
			}

			$typeHint = TokenHelper::getContent($phpcsFile, $typeHintStartPointer, $typeHintEndPointer);

			/** @var string $typeHint */
			$typeHint = preg_replace('~\s+~', '', $typeHint);

			if (!$isNullable) {
				$isNullable = preg_match('~(?:^|\|)null(?:\||$)~i', $typeHint) === 1;
			}

			$parametersTypeHints[$parameterName] = new TypeHint($typeHint, $isNullable, $typeHintStartPointer, $typeHintEndPointer);
		}

		return $parametersTypeHints;
	}

	public static function returnsValue(File $phpcsFile, int $functionPointer): bool
	{
		$tokens = $phpcsFile->getTokens();

		$firstPointerInScope = $tokens[$functionPointer]['scope_opener'] + 1;

		for ($i = $firstPointerInScope; $i < $tokens[$functionPointer]['scope_closer']; $i++) {
			if (!in_array($tokens[$i]['code'], [T_YIELD, T_YIELD_FROM], true)) {
				continue;
			}

			if (!ScopeHelper::isInSameScope($phpcsFile, $i, $firstPointerInScope)) {
				continue;
			}

			return true;
		}

		for ($i = $firstPointerInScope; $i < $tokens[$functionPointer]['scope_closer']; $i++) {
			if ($tokens[$i]['code'] !== T_RETURN) {
				continue;
			}

			if (!ScopeHelper::isInSameScope($phpcsFile, $i, $firstPointerInScope)) {
				continue;
			}

			$nextEffectiveTokenPointer = TokenHelper::findNextEffective($phpcsFile, $i + 1);
			return $tokens[$nextEffectiveTokenPointer]['code'] !== T_SEMICOLON;
		}

		return false;
	}

	public static function findReturnTypeHint(File $phpcsFile, int $functionPointer): ?TypeHint
	{
		$tokens = $phpcsFile->getTokens();

		$nextPointer = TokenHelper::findNextEffective($phpcsFile, $tokens[$functionPointer]['parenthesis_closer'] + 1);

		if ($tokens[$nextPointer]['code'] === T_USE) {
			$useParenthesisOpener = TokenHelper::findNextEffective($phpcsFile, $nextPointer + 1);
			$colonPointer = TokenHelper::findNextEffective($phpcsFile, $tokens[$useParenthesisOpener]['parenthesis_closer'] + 1);
		} else {
			$colonPointer = $nextPointer;
		}

		if ($tokens[$colonPointer]['code'] !== T_COLON) {
			return null;
		}

		$typeHintStartPointer = TokenHelper::findNextEffective($phpcsFile, $colonPointer + 1);
		$nullable = $tokens[$typeHintStartPointer]['code'] === T_NULLABLE;

		$pointerAfterTypeHint = self::isAbstract($phpcsFile, $functionPointer)
			? TokenHelper::findNext($phpcsFile, T_SEMICOLON, $typeHintStartPointer + 1)
			: $tokens[$functionPointer]['scope_opener'];

		$typeHintEndPointer = TokenHelper::findPreviousEffective($phpcsFile, $pointerAfterTypeHint - 1);

		$typeHint = TokenHelper::getContent($phpcsFile, $typeHintStartPointer, $typeHintEndPointer);

		/** @var string $typeHint */
		$typeHint = preg_replace('~\s+~', '', $typeHint);

		if (!$nullable) {
			$nullable = preg_match('~(?:^|\|)null(?:\||$)~i', $typeHint) === 1;
		}

		return new TypeHint($typeHint, $nullable, $typeHintStartPointer, $typeHintEndPointer);
	}

	public static function hasReturnTypeHint(File $phpcsFile, int $functionPointer): bool
	{
		return self::findReturnTypeHint($phpcsFile, $functionPointer) !== null;
	}

	/**
	 * @return ParameterAnnotation[]
	 */
	public static function getParametersAnnotations(File $phpcsFile, int $functionPointer): array
	{
		/** @var ParameterAnnotation[] $parametersAnnotations */
		$parametersAnnotations = AnnotationHelper::getAnnotationsByName($phpcsFile, $functionPointer, '@param');
		return $parametersAnnotations;
	}

	/**
	 * @return array<string, ParameterAnnotation>
	 */
	public static function getValidParametersAnnotations(File $phpcsFile, int $functionPointer): array
	{
		$parametersAnnotations = [];
		foreach (self::getParametersAnnotations($phpcsFile, $functionPointer) as $parameterAnnotation) {
			if ($parameterAnnotation->getContent() === null) {
				continue;
			}

			if ($parameterAnnotation->isInvalid()) {
				continue;
			}

			$parametersAnnotations[$parameterAnnotation->getParameterName()] = $parameterAnnotation;
		}

		return $parametersAnnotations;
	}

	/**
	 * @return array<string, ParameterAnnotation>
	 */
	public static function getValidPrefixedParametersAnnotations(File $phpcsFile, int $functionPointer): array
	{
		$parametersAnnotations = [];
		foreach (AnnotationHelper::PREFIXES as $prefix) {
			/** @var ParameterAnnotation[] $annotations */
			$annotations = AnnotationHelper::getAnnotationsByName($phpcsFile, $functionPointer, sprintf('@%s-param', $prefix));
			foreach ($annotations as $parameterAnnotation) {
				if ($parameterAnnotation->getContent() === null) {
					continue;
				}

				if ($parameterAnnotation->isInvalid()) {
					continue;
				}

				$parametersAnnotations[$parameterAnnotation->getParameterName()] = $parameterAnnotation;
			}
		}

		return $parametersAnnotations;
	}

	public static function findReturnAnnotation(File $phpcsFile, int $functionPointer): ?ReturnAnnotation
	{
		/** @var ReturnAnnotation[] $returnAnnotations */
		$returnAnnotations = AnnotationHelper::getAnnotationsByName($phpcsFile, $functionPointer, '@return');

		if (count($returnAnnotations) === 0) {
			return null;
		}

		return $returnAnnotations[0];
	}

	/**
	 * @return ReturnAnnotation[]
	 */
	public static function getValidPrefixedReturnAnnotations(File $phpcsFile, int $functionPointer): array
	{
		$returnAnnotations = [];

		foreach (AnnotationHelper::PREFIXES as $prefix) {
			/** @var ReturnAnnotation[] $annotations */
			$annotations = AnnotationHelper::getAnnotationsByName($phpcsFile, $functionPointer, sprintf('@%s-return', $prefix));
			foreach ($annotations as $annotation) {
				if (!$annotation->isInvalid()) {
					$returnAnnotations[] = $annotation;
					break;
				}
			}
		}

		return $returnAnnotations;
	}

	/**
	 * @return string[]
	 */
	public static function getAllFunctionNames(File $phpcsFile): array
	{
		$previousFunctionPointer = 0;

		return array_map(
			static function (int $functionPointer) use ($phpcsFile): string {
				return self::getName($phpcsFile, $functionPointer);
			},
			array_filter(
				iterator_to_array(self::getAllFunctionOrMethodPointers($phpcsFile, $previousFunctionPointer)),
				static function (int $functionOrMethodPointer) use ($phpcsFile): bool {
					return !self::isMethod($phpcsFile, $functionOrMethodPointer);
				}
			)
		);
	}

	/**
	 * @param int $flags optional bitmask of self::LINE_INCLUDE_* constants
	 */
	public static function getFunctionLengthInLines(File $file, int $functionPosition, int $flags = 0): int
	{
		if (self::isAbstract($file, $functionPosition)) {
			return 0;
		}

		$includeWhitespace = ($flags & self::LINE_INCLUDE_WHITESPACE) === self::LINE_INCLUDE_WHITESPACE;
		$includeComments = ($flags & self::LINE_INCLUDE_COMMENT) === self::LINE_INCLUDE_COMMENT;

		$tokens = $file->getTokens();
		$token = $tokens[$functionPosition];

		$tokenOpenerPosition = $token['scope_opener'];
		$tokenCloserPosition = $token['scope_closer'];
		$tokenOpenerLine = $tokens[$tokenOpenerPosition]['line'];
		$tokenCloserLine = $tokens[$tokenCloserPosition]['line'];

		$lineCount = 0;
		$lastCommentLine = null;
		$previousIncludedPosition = null;

		for ($position = $tokenOpenerPosition; $position <= $tokenCloserPosition - 1; $position++) {
			$token = $tokens[$position];
			if ($includeComments === false) {
				if (in_array($token['code'], Tokens::$commentTokens, true)) {
					if (
						$previousIncludedPosition !== null &&
						substr_count($token['content'], $file->eolChar) > 0 &&
						$token['line'] === $tokens[$previousIncludedPosition]['line']
					) {
						// Comment with linebreak starting on same line as included Token
						$lineCount++;
					}
					// Don't include comment
					$lastCommentLine = $token['line'];
					continue;
				}
				if (
					$previousIncludedPosition !== null &&
					$token['code'] === T_WHITESPACE &&
					$token['line'] === $lastCommentLine &&
					$token['line'] !== $tokens[$previousIncludedPosition]['line']
				) {
					// Whitespace after block comment... still on comment line...
					// Ignore along with the comment
					continue;
				}
			}
			if ($token['code'] === T_WHITESPACE) {
				$nextNonWhitespacePosition = $file->findNext(T_WHITESPACE, $position + 1, $tokenCloserPosition + 1, true);
				if (
					$includeWhitespace === false &&
					$token['column'] === 1 &&
					$nextNonWhitespacePosition !== false &&
					$tokens[$nextNonWhitespacePosition]['line'] !== $token['line']
				) {
					// This line is nothing but whitepace
					$position = $nextNonWhitespacePosition - 1;
					continue;
				}
				if ($previousIncludedPosition === $tokenOpenerPosition && $token['line'] === $tokenOpenerLine) {
					// Don't linclude line break after opening "{"
					// Unless there was code or an (included) comment following the "{"
					continue;
				}
			}
			if ($token['code'] !== T_WHITESPACE) {
				$previousIncludedPosition = $position;
			}
			$newLineFoundCount = substr_count($token['content'], $file->eolChar);
			$lineCount += $newLineFoundCount;
		}
		if ($tokens[$previousIncludedPosition]['line'] === $tokenCloserLine) {
			// There is code or comment on the closing "}" line...
			$lineCount++;
		}
		return $lineCount;
	}

	/**
	 * @return Generator<int>
	 */
	private static function getAllFunctionOrMethodPointers(File $phpcsFile, int &$previousFunctionPointer): Generator
	{
		do {
			$nextFunctionPointer = TokenHelper::findNext($phpcsFile, T_FUNCTION, $previousFunctionPointer + 1);
			if ($nextFunctionPointer === null) {
				break;
			}

			$previousFunctionPointer = $nextFunctionPointer;

			yield $nextFunctionPointer;
		} while (true);
	}

}
