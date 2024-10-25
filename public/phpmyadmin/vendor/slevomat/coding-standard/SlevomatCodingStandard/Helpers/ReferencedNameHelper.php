<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use function array_merge;
use function array_reverse;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function token_get_all;
use const T_ANON_CLASS;
use const T_ARRAY;
use const T_AS;
use const T_ATTRIBUTE;
use const T_BITWISE_AND;
use const T_BITWISE_OR;
use const T_CATCH;
use const T_CLASS;
use const T_CLOSE_PARENTHESIS;
use const T_COLON;
use const T_COMMA;
use const T_CONST;
use const T_DECLARE;
use const T_DOUBLE_COLON;
use const T_DOUBLE_QUOTED_STRING;
use const T_ELLIPSIS;
use const T_EXTENDS;
use const T_FUNCTION;
use const T_GOTO;
use const T_HEREDOC;
use const T_IMPLEMENTS;
use const T_INSTANCEOF;
use const T_NAMESPACE;
use const T_NEW;
use const T_NS_SEPARATOR;
use const T_NULLABLE;
use const T_NULLSAFE_OBJECT_OPERATOR;
use const T_OBJECT_OPERATOR;
use const T_OPEN_PARENTHESIS;
use const T_OPEN_SHORT_ARRAY;
use const T_OPEN_TAG;
use const T_PARAM_NAME;
use const T_STRING;
use const T_TRAIT;
use const T_TYPE_UNION;
use const T_USE;
use const T_VARIABLE;

/**
 * @internal
 */
class ReferencedNameHelper
{

	/**
	 * @return ReferencedName[]
	 */
	public static function getAllReferencedNames(File $phpcsFile, int $openTagPointer): array
	{
		$lazyValue = static function () use ($phpcsFile, $openTagPointer): array {
			return self::createAllReferencedNames($phpcsFile, $openTagPointer);
		};

		return SniffLocalCache::getAndSetIfNotCached($phpcsFile, 'references', $lazyValue);
	}

	/**
	 * @return ReferencedName[]
	 */
	public static function getAllReferencedNamesInAttributes(File $phpcsFile, int $openTagPointer): array
	{
		$lazyValue = static function () use ($phpcsFile, $openTagPointer): array {
			return self::createAllReferencedNamesInAttributes($phpcsFile, $openTagPointer);
		};

		return SniffLocalCache::getAndSetIfNotCached($phpcsFile, 'referencesFromAttributes', $lazyValue);
	}

	public static function getReferenceName(File $phpcsFile, int $nameStartPointer, int $nameEndPointer): string
	{
		$tokens = $phpcsFile->getTokens();

		$referencedName = '';
		for ($i = $nameStartPointer; $i <= $nameEndPointer; $i++) {
			if (in_array($tokens[$i]['code'], Tokens::$emptyTokens, true)) {
				continue;
			}

			$referencedName .= $tokens[$i]['content'];
		}

		return $referencedName;
	}

	public static function getReferencedNameEndPointer(File $phpcsFile, int $startPointer): int
	{
		$tokens = $phpcsFile->getTokens();

		$nameTokenCodes = TokenHelper::getNameTokenCodes();

		$nameTokenCodesWithWhitespace = array_merge($nameTokenCodes, Tokens::$emptyTokens);

		$lastNamePointer = $startPointer;
		for ($i = $startPointer + 1; $i < count($tokens); $i++) {
			if (!in_array($tokens[$i]['code'], $nameTokenCodesWithWhitespace, true)) {
				break;
			}

			if (!in_array($tokens[$i]['code'], $nameTokenCodes, true)) {
				continue;
			}

			$lastNamePointer = $i;
		}

		return $lastNamePointer;
	}

	/**
	 * @return ReferencedName[]
	 */
	private static function createAllReferencedNames(File $phpcsFile, int $openTagPointer): array
	{
		$referencedNames = [];

		$beginSearchAtPointer = $openTagPointer + 1;
		$nameTokenCodes = TokenHelper::getNameTokenCodes();
		$nameTokenCodes[] = T_DOUBLE_QUOTED_STRING;
		$nameTokenCodes[] = T_HEREDOC;

		$tokens = $phpcsFile->getTokens();
		while (true) {
			$nameStartPointer = TokenHelper::findNext($phpcsFile, $nameTokenCodes, $beginSearchAtPointer);
			if ($nameStartPointer === null) {
				break;
			}

			// Find referenced names inside double quotes string
			if (self::isNeedParsedContent($tokens[$nameStartPointer]['code'])) {
				$content = $tokens[$nameStartPointer]['content'];
				if (self::isNeedParsedContent($tokens[$nameStartPointer - 1]['code'])) {
					$content = '"' . $content;
				}
				if (self::isNeedParsedContent($tokens[$nameStartPointer + 1]['code'])) {
					$content .= '"';
				}
				$names = self::getReferencedNamesFromString($content);
				foreach ($names as $name) {
					$referencedNames[] = new ReferencedName($name, $nameStartPointer, $nameStartPointer, ReferencedName::TYPE_CLASS);
				}
				$beginSearchAtPointer = $nameStartPointer + 1;
				continue;
			}

			// Attributes are parsed in specific method
			$attributeStartPointerBefore = TokenHelper::findPrevious($phpcsFile, T_ATTRIBUTE, $nameStartPointer - 1, $beginSearchAtPointer);
			if ($attributeStartPointerBefore !== null) {
				if ($tokens[$attributeStartPointerBefore]['attribute_closer'] > $nameStartPointer) {
					$beginSearchAtPointer = $tokens[$attributeStartPointerBefore]['attribute_closer'] + 1;
					continue;
				}
			}

			if (!self::isReferencedName($phpcsFile, $nameStartPointer)) {
				/** @var int $beginSearchAtPointer */
				$beginSearchAtPointer = TokenHelper::findNextExcluding(
					$phpcsFile,
					array_merge(TokenHelper::$ineffectiveTokenCodes, $nameTokenCodes),
					$nameStartPointer + 1
				);
				continue;
			}

			$nameEndPointer = self::getReferencedNameEndPointer($phpcsFile, $nameStartPointer);

			$referencedNames[] = new ReferencedName(
				self::getReferenceName($phpcsFile, $nameStartPointer, $nameEndPointer),
				$nameStartPointer,
				$nameEndPointer,
				self::getReferenceType($phpcsFile, $nameStartPointer, $nameEndPointer)
			);
			$beginSearchAtPointer = $nameEndPointer + 1;
		}
		return $referencedNames;
	}

	private static function getReferenceType(File $phpcsFile, int $nameStartPointer, int $nameEndPointer): string
	{
		$tokens = $phpcsFile->getTokens();

		$nextTokenAfterEndPointer = TokenHelper::findNextEffective($phpcsFile, $nameEndPointer + 1);
		$previousTokenBeforeStartPointer = TokenHelper::findPreviousEffective($phpcsFile, $nameStartPointer - 1);

		$nameTokenCodes = TokenHelper::getNameTokenCodes();

		if ($tokens[$nextTokenAfterEndPointer]['code'] === T_OPEN_PARENTHESIS) {
			return $tokens[$previousTokenBeforeStartPointer]['code'] === T_NEW
				? ReferencedName::TYPE_CLASS
				: ReferencedName::TYPE_FUNCTION;
		}

		if (
			$tokens[$previousTokenBeforeStartPointer]['code'] === T_TYPE_UNION
			|| $tokens[$nextTokenAfterEndPointer]['code'] === T_TYPE_UNION
		) {
			return ReferencedName::TYPE_CLASS;
		}

		if ($tokens[$nextTokenAfterEndPointer]['code'] === T_BITWISE_AND) {
			$tokenAfterNextToken = TokenHelper::findNextEffective($phpcsFile, $nextTokenAfterEndPointer + 1);

			return in_array($tokens[$tokenAfterNextToken]['code'], [T_VARIABLE, T_ELLIPSIS], true)
				? ReferencedName::TYPE_CLASS
				: ReferencedName::TYPE_CONSTANT;
		}

		if (
			in_array($tokens[$nextTokenAfterEndPointer]['code'], [
				T_VARIABLE,
				// Variadic parameter
				T_ELLIPSIS,
			], true)
		) {
			return ReferencedName::TYPE_CLASS;
		}

		if ($tokens[$previousTokenBeforeStartPointer]['code'] === T_COLON) {
			$previousTokenPointer = TokenHelper::findPreviousEffective($phpcsFile, $previousTokenBeforeStartPointer - 1);

			if (
				$tokens[$previousTokenPointer]['code'] === T_PARAM_NAME
				&& $tokens[$nextTokenAfterEndPointer]['code'] !== T_DOUBLE_COLON
			) {
				return ReferencedName::TYPE_CONSTANT;
			}

			// Return type hint
			return ReferencedName::TYPE_CLASS;
		}

		if (
			in_array($tokens[$previousTokenBeforeStartPointer]['code'], [
				T_EXTENDS,
				T_IMPLEMENTS,
				T_INSTANCEOF,
				// Trait
				T_USE,
				T_NEW,
				// Nullable type hint
				T_NULLABLE,
			], true)
			|| $tokens[$nextTokenAfterEndPointer]['code'] === T_DOUBLE_COLON
		) {
			return ReferencedName::TYPE_CLASS;
		}

		if ($tokens[$previousTokenBeforeStartPointer]['code'] === T_COMMA) {
			$previousTokenPointer = TokenHelper::findPreviousExcluding(
				$phpcsFile,
				array_merge([T_COMMA], $nameTokenCodes, TokenHelper::$ineffectiveTokenCodes),
				$previousTokenBeforeStartPointer - 1
			);

			return in_array($tokens[$previousTokenPointer]['code'], [
				T_IMPLEMENTS,
				T_EXTENDS,
				T_USE,
			], true)
				? ReferencedName::TYPE_CLASS
				: ReferencedName::TYPE_CONSTANT;
		}

		if (in_array($tokens[$previousTokenBeforeStartPointer]['code'], [T_BITWISE_OR, T_OPEN_PARENTHESIS], true)) {
			$catchPointer = TokenHelper::findPreviousExcluding(
				$phpcsFile,
				array_merge([T_BITWISE_OR, T_OPEN_PARENTHESIS], $nameTokenCodes, TokenHelper::$ineffectiveTokenCodes),
				$previousTokenBeforeStartPointer - 1
			);

			if ($tokens[$catchPointer]['code'] === T_CATCH) {
				return ReferencedName::TYPE_CLASS;
			}
		}

		return ReferencedName::TYPE_CONSTANT;
	}

	private static function isReferencedName(File $phpcsFile, int $startPointer): bool
	{
		$tokens = $phpcsFile->getTokens();

		$nextPointer = TokenHelper::findNextEffective($phpcsFile, $startPointer + 1);
		$previousPointer = TokenHelper::findPreviousEffective($phpcsFile, $startPointer - 1);

		if ($nextPointer !== null && $tokens[$nextPointer]['code'] === T_DOUBLE_COLON) {
			return !in_array($tokens[$previousPointer]['code'], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true);
		}

		if (
			count($tokens[$startPointer]['conditions']) > 0
			&& array_values(array_reverse($tokens[$startPointer]['conditions']))[0] === T_USE
		) {
			// Method imported from trait
			return false;
		}

		$previousToken = $tokens[$previousPointer];

		$skipTokenCodes = [
			T_FUNCTION,
			T_AS,
			T_DOUBLE_COLON,
			T_OBJECT_OPERATOR,
			T_NULLSAFE_OBJECT_OPERATOR,
			T_NAMESPACE,
			T_CONST,
		];

		if ($previousToken['code'] === T_USE) {
			$classPointer = TokenHelper::findPrevious($phpcsFile, [T_CLASS, T_TRAIT, T_ANON_CLASS], $startPointer - 1);
			if ($classPointer !== null) {
				$classToken = $tokens[$classPointer];
				return $startPointer > $classToken['scope_opener'] && $startPointer < $classToken['scope_closer'];
			}

			return false;
		}

		if (
			$previousToken['code'] === T_OPEN_PARENTHESIS
			&& isset($previousToken['parenthesis_owner'])
			&& $tokens[$previousToken['parenthesis_owner']]['code'] === T_DECLARE
		) {
			return false;
		}

		if (
			$previousToken['code'] === T_COMMA
			&& TokenHelper::findPreviousLocal($phpcsFile, T_DECLARE, $previousPointer - 1) !== null
		) {
			return false;
		}

		if ($previousToken['code'] === T_COMMA) {
			$constPointer = TokenHelper::findPreviousLocal($phpcsFile, T_CONST, $previousPointer - 1);
			if (
				$constPointer !== null
				&& TokenHelper::findNext($phpcsFile, [T_OPEN_SHORT_ARRAY, T_ARRAY], $constPointer + 1, $startPointer) === null
			) {
				return false;
			}
		} elseif ($previousToken['code'] === T_BITWISE_AND) {
			$pointerBefore = TokenHelper::findPreviousEffective($phpcsFile, $previousPointer - 1);
			$isFunctionPointerBefore = TokenHelper::findPreviousLocal($phpcsFile, T_FUNCTION, $previousPointer - 1) !== null;

			if ($tokens[$pointerBefore]['code'] !== T_VARIABLE && $isFunctionPointerBefore) {
				return false;
			}
		} elseif ($previousToken['code'] === T_GOTO) {
			return false;
		}

		$isProbablyReferencedName = !in_array(
			$previousToken['code'],
			array_merge($skipTokenCodes, TokenHelper::$typeKeywordTokenCodes),
			true
		);

		if (!$isProbablyReferencedName) {
			return false;
		}

		$endPointer = self::getReferencedNameEndPointer($phpcsFile, $startPointer);
		$referencedName = self::getReferenceName($phpcsFile, $startPointer, $endPointer);

		if (TypeHintHelper::isSimpleTypeHint($referencedName) || $referencedName === 'object') {
			return $tokens[$nextPointer]['code'] === T_OPEN_PARENTHESIS;
		}

		return true;
	}

	/**
	 * @return ReferencedName[]
	 */
	private static function createAllReferencedNamesInAttributes(File $phpcsFile, int $openTagPointer): array
	{
		$referencedNames = [];

		$tokens = $phpcsFile->getTokens();

		$attributePointers = TokenHelper::findNextAll($phpcsFile, T_ATTRIBUTE, $openTagPointer + 1);

		foreach ($attributePointers as $attributeStartPointer) {
			$searchStartPointer = $attributeStartPointer + 1;
			$searchEndPointer = $tokens[$attributeStartPointer]['attribute_closer'];

			$searchPointer = $searchStartPointer;
			$searchTokens = array_merge(TokenHelper::getNameTokenCodes(), [T_OPEN_PARENTHESIS, T_CLOSE_PARENTHESIS]);
			$level = 0;
			do {
				$pointer = TokenHelper::findNext($phpcsFile, $searchTokens, $searchPointer, $searchEndPointer);

				if ($pointer === null) {
					break;
				}

				if ($tokens[$pointer]['code'] === T_OPEN_PARENTHESIS) {
					$level++;
					$searchPointer = $pointer + 1;
					continue;
				}

				if ($tokens[$pointer]['code'] === T_CLOSE_PARENTHESIS) {
					$level--;
					$searchPointer = $pointer + 1;
					continue;
				}

				$referencedNameEndPointer = self::getReferencedNameEndPointer($phpcsFile, $pointer);

				$pointerBefore = TokenHelper::findPreviousEffective($phpcsFile, $pointer - 1);

				if (in_array($tokens[$pointerBefore]['code'], [T_OPEN_TAG, T_ATTRIBUTE], true)) {
					$referenceType = ReferencedName::TYPE_CLASS;
				} elseif ($tokens[$pointerBefore]['code'] === T_COMMA && $level === 0) {
					$referenceType = ReferencedName::TYPE_CLASS;
				} elseif (self::isReferencedName($phpcsFile, $pointer)) {
					$referenceType = self::getReferenceType($phpcsFile, $pointer, $referencedNameEndPointer);
				} else {
					$searchPointer = $pointer + 1;
					continue;
				}

				$referencedName = self::getReferenceName($phpcsFile, $pointer, $referencedNameEndPointer);

				$referencedNames[] = new ReferencedName(
					$referencedName,
					$attributeStartPointer,
					$tokens[$attributeStartPointer]['attribute_closer'],
					$referenceType
				);

				$searchPointer = $referencedNameEndPointer + 1;

			} while (true);
		}

		return $referencedNames;
	}

	/**
	 * @param int|string $code
	 */
	private static function isNeedParsedContent($code): bool
	{
		return in_array($code, [T_DOUBLE_QUOTED_STRING, T_HEREDOC], true);
	}

	/**
	 * @return string[]
	 */
	private static function getReferencedNamesFromString(string $content): array
	{
		$referencedNames = [];
		$subTokens = token_get_all('<?php ' . $content);

		foreach ($subTokens as $position => $token) {
			if (is_array($token) && $token[0] === T_DOUBLE_COLON) {
				$referencedName = '';
				$tmpPosition = $position - 1;
				while (true) {
					if (!is_array($subTokens[$tmpPosition]) || !in_array($subTokens[$tmpPosition][0], [T_NS_SEPARATOR, T_STRING], true)) {
						break;
					}

					$referencedName = $subTokens[$tmpPosition][1] . $referencedName;
					$tmpPosition--;
				}

				$referencedNames[] = $referencedName;
			}
		}

		return $referencedNames;
	}

}
