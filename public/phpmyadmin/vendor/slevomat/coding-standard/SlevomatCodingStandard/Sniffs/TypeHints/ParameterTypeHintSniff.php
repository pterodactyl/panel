<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use SlevomatCodingStandard\Helpers\Annotation\ParameterAnnotation;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\AnnotationTypeHelper;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\SuppressHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\TypeHint;
use SlevomatCodingStandard\Helpers\TypeHintHelper;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function in_array;
use function lcfirst;
use function sprintf;
use function strtolower;
use const T_BITWISE_AND;
use const T_DOC_COMMENT_CLOSE_TAG;
use const T_DOC_COMMENT_STAR;
use const T_ELLIPSIS;
use const T_FUNCTION;
use const T_VARIABLE;

class ParameterTypeHintSniff implements Sniff
{

	public const CODE_MISSING_ANY_TYPE_HINT = 'MissingAnyTypeHint';

	public const CODE_MISSING_NATIVE_TYPE_HINT = 'MissingNativeTypeHint';

	public const CODE_MISSING_TRAVERSABLE_TYPE_HINT_SPECIFICATION = 'MissingTraversableTypeHintSpecification';

	public const CODE_USELESS_ANNOTATION = 'UselessAnnotation';

	public const CODE_USELESS_SUPPRESS = 'UselessSuppress';

	private const NAME = 'SlevomatCodingStandard.TypeHints.ParameterTypeHint';

	/** @var bool|null */
	public $enableObjectTypeHint = null;

	/** @var bool|null */
	public $enableMixedTypeHint = null;

	/** @var bool|null */
	public $enableUnionTypeHint = null;

	/** @var string[] */
	public $traversableTypeHints = [];

	/** @var array<int, string>|null */
	private $normalizedTraversableTypeHints;

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [
			T_FUNCTION,
		];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $functionPointer
	 */
	public function process(File $phpcsFile, $functionPointer): void
	{
		$this->enableObjectTypeHint = SniffSettingsHelper::isEnabledByPhpVersion($this->enableObjectTypeHint, 70200);
		$this->enableMixedTypeHint = SniffSettingsHelper::isEnabledByPhpVersion($this->enableMixedTypeHint, 80000);
		$this->enableUnionTypeHint = SniffSettingsHelper::isEnabledByPhpVersion($this->enableUnionTypeHint, 80000);

		if (SuppressHelper::isSniffSuppressed($phpcsFile, $functionPointer, self::NAME)) {
			return;
		}

		if (DocCommentHelper::hasInheritdocAnnotation($phpcsFile, $functionPointer)) {
			return;
		}

		$parametersTypeHints = FunctionHelper::getParametersTypeHints($phpcsFile, $functionPointer);
		$parametersAnnotations = FunctionHelper::getValidParametersAnnotations($phpcsFile, $functionPointer);
		$prefixedParametersAnnotations = FunctionHelper::getValidPrefixedParametersAnnotations($phpcsFile, $functionPointer);

		$this->checkTypeHints($phpcsFile, $functionPointer, $parametersTypeHints, $parametersAnnotations, $prefixedParametersAnnotations);
		$this->checkTraversableTypeHintSpecification(
			$phpcsFile,
			$functionPointer,
			$parametersTypeHints,
			$parametersAnnotations,
			$prefixedParametersAnnotations
		);
		$this->checkUselessAnnotations($phpcsFile, $functionPointer, $parametersTypeHints, $parametersAnnotations);
	}

	/**
	 * @param (TypeHint|null)[] $parametersTypeHints
	 * @param ParameterAnnotation[] $parametersAnnotations
	 * @param ParameterAnnotation[] $prefixedParametersAnnotations
	 */
	private function checkTypeHints(
		File $phpcsFile,
		int $functionPointer,
		array $parametersTypeHints,
		array $parametersAnnotations,
		array $prefixedParametersAnnotations
	): void
	{
		$suppressNameAnyTypeHint = self::getSniffName(self::CODE_MISSING_ANY_TYPE_HINT);
		$isSuppressedAnyTypeHint = SuppressHelper::isSniffSuppressed($phpcsFile, $functionPointer, $suppressNameAnyTypeHint);

		$suppressNameNativeTypeHint = $this->getSniffName(self::CODE_MISSING_NATIVE_TYPE_HINT);
		$isSuppressedNativeTypeHint = SuppressHelper::isSniffSuppressed($phpcsFile, $functionPointer, $suppressNameNativeTypeHint);

		$suppressedErrors = 0;

		$parametersWithoutTypeHint = array_keys(
			array_filter($parametersTypeHints, static function (?TypeHint $parameterTypeHint = null): bool {
				return $parameterTypeHint === null;
			})
		);

		foreach ($parametersWithoutTypeHint as $parameterName) {
			if (!array_key_exists($parameterName, $parametersAnnotations)) {
				if (array_key_exists($parameterName, $prefixedParametersAnnotations)) {
					continue;
				}

				if ($isSuppressedAnyTypeHint) {
					$suppressedErrors++;
					continue;
				}

				$phpcsFile->addError(
					sprintf(
						'%s %s() does not have parameter type hint nor @param annotation for its parameter %s.',
						FunctionHelper::getTypeLabel($phpcsFile, $functionPointer),
						FunctionHelper::getFullyQualifiedName($phpcsFile, $functionPointer),
						$parameterName
					),
					$functionPointer,
					self::CODE_MISSING_ANY_TYPE_HINT
				);

				continue;
			}

			$parameterTypeNode = $parametersAnnotations[$parameterName]->getType();

			if ($parameterTypeNode instanceof IdentifierTypeNode && strtolower($parameterTypeNode->name) === 'null') {
				continue;
			}

			$originalParameterTypeNode = $parameterTypeNode;
			if ($parameterTypeNode instanceof NullableTypeNode) {
				$parameterTypeNode = $parameterTypeNode->type;
			}

			$canTryUnionTypeHint = $this->enableUnionTypeHint && $parameterTypeNode instanceof UnionTypeNode;

			$typeHints = [];
			$traversableTypeHints = [];
			$nullableParameterTypeHint = false;

			if (AnnotationTypeHelper::containsOneType($parameterTypeNode)) {
				/** @var ArrayTypeNode|ArrayShapeNode|IdentifierTypeNode|ThisTypeNode|GenericTypeNode|CallableTypeNode|ConstTypeNode $parameterTypeNode */
				$parameterTypeNode = $parameterTypeNode;
				$typeHints[] = AnnotationTypeHelper::getTypeHintFromOneType($parameterTypeNode);

			} elseif (
				$parameterTypeNode instanceof UnionTypeNode
				|| $parameterTypeNode instanceof IntersectionTypeNode
			) {
				$traversableTypeHints = [];
				foreach ($parameterTypeNode->types as $typeNode) {
					if (!AnnotationTypeHelper::containsOneType($typeNode)) {
						continue 2;
					}

					/** @var ArrayTypeNode|ArrayShapeNode|IdentifierTypeNode|ThisTypeNode|GenericTypeNode|CallableTypeNode|ConstTypeNode $typeNode */
					$typeNode = $typeNode;

					$typeHint = AnnotationTypeHelper::getTypeHintFromOneType($typeNode, $canTryUnionTypeHint);

					if (strtolower($typeHint) === 'null') {
						$nullableParameterTypeHint = true;
						continue;
					}

					$isTraversable = TypeHintHelper::isTraversableType(
						TypeHintHelper::getFullyQualifiedTypeHint($phpcsFile, $functionPointer, $typeHint),
						$this->getTraversableTypeHints()
					);

					if (
						!$typeNode instanceof ArrayTypeNode
						&& !$typeNode instanceof ArrayShapeNode
						&& $isTraversable
					) {
						$traversableTypeHints[] = $typeHint;
					}

					$typeHints[] = $typeHint;
				}

				$traversableTypeHints = array_values(array_unique($traversableTypeHints));
				if (count($traversableTypeHints) > 1 && !$canTryUnionTypeHint) {
					continue;
				}
			}

			$typeHints = array_values(array_unique($typeHints));

			if (count($traversableTypeHints) > 0) {
				/** @var UnionTypeNode|IntersectionTypeNode $parameterTypeNode */
				$parameterTypeNode = $parameterTypeNode;

				$itemsSpecificationTypeHint = AnnotationTypeHelper::getItemsSpecificationTypeFromType($parameterTypeNode);
				if ($itemsSpecificationTypeHint !== null) {
					$typeHints = AnnotationTypeHelper::getTraversableTypeHintsFromType(
						$parameterTypeNode,
						$phpcsFile,
						$functionPointer,
						$this->getTraversableTypeHints(),
						$canTryUnionTypeHint
					);
				}
			}

			if (count($typeHints) === 0) {
				continue;
			}

			$typeHintsWithConvertedUnion = [];
			foreach ($typeHints as $typeHint) {
				if ($this->enableUnionTypeHint && TypeHintHelper::isUnofficialUnionTypeHint($typeHint)) {
					$canTryUnionTypeHint = true;
					$typeHintsWithConvertedUnion = array_merge(
						$typeHintsWithConvertedUnion,
						TypeHintHelper::convertUnofficialUnionTypeHintToOfficialTypeHints($typeHint)
					);
				} else {
					$typeHintsWithConvertedUnion[] = $typeHint;
				}
			}
			$typeHintsWithConvertedUnion = array_unique($typeHintsWithConvertedUnion);

			if (count($typeHintsWithConvertedUnion) > 1 && !$canTryUnionTypeHint) {
				continue;
			}

			foreach ($typeHintsWithConvertedUnion as $typeHintNo => $typeHint) {
				if ($canTryUnionTypeHint && $typeHint === 'false') {
					continue;
				}

				if (!TypeHintHelper::isValidTypeHint($typeHint, $this->enableObjectTypeHint, false, $this->enableMixedTypeHint)) {
					continue 2;
				}

				if (TypeHintHelper::isTypeDefinedInAnnotation($phpcsFile, $functionPointer, $typeHint)) {
					continue 2;
				}

				$typeHintsWithConvertedUnion[$typeHintNo] = TypeHintHelper::convertLongSimpleTypeHintToShort($typeHint);
			}

			if ($originalParameterTypeNode instanceof NullableTypeNode) {
				$nullableParameterTypeHint = true;
			}

			if ($isSuppressedNativeTypeHint) {
				$suppressedErrors++;
				continue;
			}

			$fix = $phpcsFile->addFixableError(
				sprintf(
					'%s %s() does not have native type hint for its parameter %s but it should be possible to add it based on @param annotation "%s".',
					FunctionHelper::getTypeLabel($phpcsFile, $functionPointer),
					FunctionHelper::getFullyQualifiedName($phpcsFile, $functionPointer),
					$parameterName,
					AnnotationTypeHelper::export($parameterTypeNode)
				),
				$functionPointer,
				self::CODE_MISSING_NATIVE_TYPE_HINT
			);
			if (!$fix) {
				continue;
			}

			if (in_array('mixed', $typeHintsWithConvertedUnion, true)) {
				$parameterTypeHint = 'mixed';
			} else {
				$parameterTypeHint = implode('|', $typeHintsWithConvertedUnion);
				if ($nullableParameterTypeHint) {
					if (count($typeHintsWithConvertedUnion) > 1) {
						$parameterTypeHint .= '|null';
					} else {
						$parameterTypeHint = '?' . $parameterTypeHint;
					}
				}
			}

			$tokens = $phpcsFile->getTokens();
			/** @var int $parameterPointer */
			$parameterPointer = TokenHelper::findNextContent(
				$phpcsFile,
				T_VARIABLE,
				$parameterName,
				$tokens[$functionPointer]['parenthesis_opener'],
				$tokens[$functionPointer]['parenthesis_closer']
			);

			$beforeParameterPointer = $parameterPointer;
			do {
				$previousPointer = TokenHelper::findPreviousEffective(
					$phpcsFile,
					$beforeParameterPointer - 1,
					$tokens[$functionPointer]['parenthesis_opener'] + 1
				);
				if (
					$previousPointer === null
					|| !in_array($tokens[$previousPointer]['code'], [T_BITWISE_AND, T_ELLIPSIS], true)
				) {
					break;
				}

				/** @var int $beforeParameterPointer */
				$beforeParameterPointer = $previousPointer;
			} while (true);

			$phpcsFile->fixer->beginChangeset();
			$phpcsFile->fixer->addContentBefore(
				$beforeParameterPointer,
				sprintf('%s ', $parameterTypeHint)
			);
			$phpcsFile->fixer->endChangeset();
		}

		if ($suppressedErrors > 0) {
			return;
		}

		if ($isSuppressedAnyTypeHint) {
			$this->reportUselessSuppress($phpcsFile, $functionPointer, $suppressNameAnyTypeHint);
		}

		if ($isSuppressedNativeTypeHint) {
			$this->reportUselessSuppress($phpcsFile, $functionPointer, $suppressNameNativeTypeHint);
		}
	}

	/**
	 * @param (TypeHint|null)[] $parametersTypeHints
	 * @param ParameterAnnotation[] $parametersAnnotations
	 * @param ParameterAnnotation[] $prefixedParametersAnnotations
	 */
	private function checkTraversableTypeHintSpecification(
		File $phpcsFile,
		int $functionPointer,
		array $parametersTypeHints,
		array $parametersAnnotations,
		array $prefixedParametersAnnotations
	): void
	{
		$suppressName = self::getSniffName(self::CODE_MISSING_TRAVERSABLE_TYPE_HINT_SPECIFICATION);
		$isSniffSuppressed = SuppressHelper::isSniffSuppressed($phpcsFile, $functionPointer, $suppressName);
		$suppressUseless = true;

		foreach ($parametersTypeHints as $parameterName => $parameterTypeHint) {
			if (array_key_exists($parameterName, $prefixedParametersAnnotations)) {
				continue;
			}

			$hasTraversableTypeHint = false;
			if (
				$parameterTypeHint !== null
				&& TypeHintHelper::isTraversableType(
					TypeHintHelper::getFullyQualifiedTypeHint($phpcsFile, $functionPointer, $parameterTypeHint->getTypeHint()),
					$this->getTraversableTypeHints()
				)
			) {
				$hasTraversableTypeHint = true;
			} elseif (
				array_key_exists($parameterName, $parametersAnnotations)
				&& AnnotationTypeHelper::containsTraversableType(
					$parametersAnnotations[$parameterName]->getType(),
					$phpcsFile,
					$functionPointer,
					$this->getTraversableTypeHints()
				)
			) {
				$hasTraversableTypeHint = true;
			}

			if ($hasTraversableTypeHint && !array_key_exists($parameterName, $parametersAnnotations)) {
				$suppressUseless = false;

				if (!$isSniffSuppressed) {
					$phpcsFile->addError(
						sprintf(
							'%s %s() does not have @param annotation for its traversable parameter %s.',
							FunctionHelper::getTypeLabel($phpcsFile, $functionPointer),
							FunctionHelper::getFullyQualifiedName($phpcsFile, $functionPointer),
							$parameterName
						),
						$functionPointer,
						self::CODE_MISSING_TRAVERSABLE_TYPE_HINT_SPECIFICATION
					);
				}

				continue;
			}

			if (!array_key_exists($parameterName, $parametersAnnotations)) {
				continue;
			}

			$parameterTypeNode = $parametersAnnotations[$parameterName]->getType();

			if (
				(
					!$hasTraversableTypeHint
					&& !AnnotationTypeHelper::containsTraversableType(
						$parameterTypeNode,
						$phpcsFile,
						$functionPointer,
						$this->getTraversableTypeHints()
					)
				)
				|| AnnotationTypeHelper::containsItemsSpecificationForTraversable(
					$parameterTypeNode,
					$phpcsFile,
					$functionPointer,
					$this->getTraversableTypeHints()
				)
			) {
				continue;
			}

			$suppressUseless = false;

			if ($isSniffSuppressed) {
				continue;
			}

			$phpcsFile->addError(
				sprintf(
					'@param annotation of %s %s() does not specify type hint for items of its traversable parameter %s.',
					lcfirst(FunctionHelper::getTypeLabel($phpcsFile, $functionPointer)),
					FunctionHelper::getFullyQualifiedName($phpcsFile, $functionPointer),
					$parameterName
				),
				$parametersAnnotations[$parameterName]->getStartPointer(),
				self::CODE_MISSING_TRAVERSABLE_TYPE_HINT_SPECIFICATION
			);
		}

		if ($isSniffSuppressed && $suppressUseless) {
			$this->reportUselessSuppress($phpcsFile, $functionPointer, $suppressName);
		}
	}

	/**
	 * @param (TypeHint|null)[] $parametersTypeHints
	 * @param ParameterAnnotation[] $parametersAnnotations
	 */
	private function checkUselessAnnotations(
		File $phpcsFile,
		int $functionPointer,
		array $parametersTypeHints,
		array $parametersAnnotations
	): void
	{
		$suppressName = self::getSniffName(self::CODE_USELESS_ANNOTATION);
		$isSniffSuppressed = SuppressHelper::isSniffSuppressed($phpcsFile, $functionPointer, $suppressName);
		$suppressUseless = true;

		foreach ($parametersTypeHints as $parameterName => $parameterTypeHint) {
			if (!array_key_exists($parameterName, $parametersAnnotations)) {
				continue;
			}

			$parameterAnnotation = $parametersAnnotations[$parameterName];

			if (!AnnotationHelper::isAnnotationUseless(
				$phpcsFile,
				$functionPointer,
				$parameterTypeHint,
				$parameterAnnotation,
				$this->getTraversableTypeHints(),
				$this->enableUnionTypeHint
			)) {
				continue;
			}

			$suppressUseless = false;

			if ($isSniffSuppressed) {
				continue;
			}

			$fix = $phpcsFile->addFixableError(
				sprintf(
					'%s %s() has useless @param annotation for parameter %s.',
					FunctionHelper::getTypeLabel($phpcsFile, $functionPointer),
					FunctionHelper::getFullyQualifiedName($phpcsFile, $functionPointer),
					$parameterName
				),
				$parameterAnnotation->getStartPointer(),
				self::CODE_USELESS_ANNOTATION
			);
			if (!$fix) {
				continue;
			}

			$docCommentOpenPointer = DocCommentHelper::findDocCommentOpenPointer($phpcsFile, $functionPointer);
			$starPointer = TokenHelper::findPrevious(
				$phpcsFile,
				T_DOC_COMMENT_STAR,
				$parameterAnnotation->getStartPointer() - 1,
				$docCommentOpenPointer
			);

			$changeStart = $starPointer ?? $docCommentOpenPointer + 1;
			/** @var int $changeEnd */
			$changeEnd = TokenHelper::findNext(
				$phpcsFile,
				[T_DOC_COMMENT_CLOSE_TAG, T_DOC_COMMENT_STAR],
				$parameterAnnotation->getEndPointer() + 1
			) - 1;
			$phpcsFile->fixer->beginChangeset();
			for ($i = $changeStart; $i <= $changeEnd; $i++) {
				$phpcsFile->fixer->replaceToken($i, '');
			}
			$phpcsFile->fixer->endChangeset();
		}

		if ($isSniffSuppressed && $suppressUseless) {
			$this->reportUselessSuppress($phpcsFile, $functionPointer, $suppressName);
		}
	}

	private function reportUselessSuppress(File $phpcsFile, int $pointer, string $suppressName): void
	{
		$fix = $phpcsFile->addFixableError(
			sprintf('Useless %s %s', SuppressHelper::ANNOTATION, $suppressName),
			$pointer,
			self::CODE_USELESS_SUPPRESS
		);

		if ($fix) {
			SuppressHelper::removeSuppressAnnotation($phpcsFile, $pointer, $suppressName);
		}
	}

	private function getSniffName(string $sniffName): string
	{
		return sprintf('%s.%s', self::NAME, $sniffName);
	}

	/**
	 * @return array<int, string>
	 */
	private function getTraversableTypeHints(): array
	{
		if ($this->normalizedTraversableTypeHints === null) {
			$this->normalizedTraversableTypeHints = array_map(static function (string $typeHint): string {
				return NamespaceHelper::isFullyQualifiedName($typeHint)
					? $typeHint
					: sprintf('%s%s', NamespaceHelper::NAMESPACE_SEPARATOR, $typeHint);
			}, SniffSettingsHelper::normalizeArray($this->traversableTypeHints));
		}
		return $this->normalizedTraversableTypeHints;
	}

}
