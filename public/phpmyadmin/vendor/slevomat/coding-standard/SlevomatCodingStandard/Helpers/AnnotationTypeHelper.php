<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeForParameterNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\OffsetAccessTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use function array_merge;
use function count;
use function in_array;
use function preg_replace;
use function sprintf;
use function strtolower;
use function substr;

/**
 * @internal
 */
class AnnotationTypeHelper
{

	/**
	 * @return IdentifierTypeNode[]|ThisTypeNode[]
	 */
	public static function getIdentifierTypeNodes(TypeNode $typeNode): array
	{
		if ($typeNode instanceof ArrayTypeNode) {
			return self::getIdentifierTypeNodes($typeNode->type);
		}

		if ($typeNode instanceof ArrayShapeNode) {
			$identifierTypeNodes = [];
			foreach ($typeNode->items as $arrayShapeItemNode) {
				$identifierTypeNodes = array_merge($identifierTypeNodes, self::getIdentifierTypeNodes($arrayShapeItemNode->valueType));
			}
			return $identifierTypeNodes;
		}

		if (
			$typeNode instanceof UnionTypeNode
			|| $typeNode instanceof IntersectionTypeNode
		) {
			$identifierTypeNodes = [];
			foreach ($typeNode->types as $innerTypeNode) {
				$identifierTypeNodes = array_merge($identifierTypeNodes, self::getIdentifierTypeNodes($innerTypeNode));
			}
			return $identifierTypeNodes;
		}

		if ($typeNode instanceof GenericTypeNode) {
			$identifierTypeNodes = self::getIdentifierTypeNodes($typeNode->type);
			foreach ($typeNode->genericTypes as $innerTypeNode) {
				$identifierTypeNodes = array_merge($identifierTypeNodes, self::getIdentifierTypeNodes($innerTypeNode));
			}
			return $identifierTypeNodes;
		}

		if ($typeNode instanceof NullableTypeNode) {
			return self::getIdentifierTypeNodes($typeNode->type);
		}

		if ($typeNode instanceof CallableTypeNode) {
			$identifierTypeNodes = array_merge([$typeNode->identifier], self::getIdentifierTypeNodes($typeNode->returnType));
			foreach ($typeNode->parameters as $callableParameterNode) {
				$identifierTypeNodes = array_merge($identifierTypeNodes, self::getIdentifierTypeNodes($callableParameterNode->type));
			}
			return $identifierTypeNodes;
		}

		if ($typeNode instanceof ConstTypeNode) {
			return [];
		}

		if ($typeNode instanceof ConditionalTypeNode) {
			return array_merge(
				self::getIdentifierTypeNodes($typeNode->subjectType),
				self::getIdentifierTypeNodes($typeNode->targetType),
				self::getIdentifierTypeNodes($typeNode->if),
				self::getIdentifierTypeNodes($typeNode->else)
			);
		}

		if ($typeNode instanceof ConditionalTypeForParameterNode) {
			return array_merge(
				self::getIdentifierTypeNodes($typeNode->targetType),
				self::getIdentifierTypeNodes($typeNode->if),
				self::getIdentifierTypeNodes($typeNode->else)
			);
		}

		if ($typeNode instanceof OffsetAccessTypeNode) {
			return array_merge(
				self::getIdentifierTypeNodes($typeNode->type),
				self::getIdentifierTypeNodes($typeNode->offset)
			);
		}

		/** @var IdentifierTypeNode|ThisTypeNode $typeNode */
		$typeNode = $typeNode;
		return [$typeNode];
	}

	/**
	 * @return ConstTypeNode[]
	 */
	public static function getConstantTypeNodes(TypeNode $typeNode): array
	{
		if ($typeNode instanceof ArrayTypeNode) {
			return self::getConstantTypeNodes($typeNode->type);
		}

		if ($typeNode instanceof ArrayShapeNode) {
			$constTypeNodes = [];
			foreach ($typeNode->items as $arrayShapeItemNode) {
				$constTypeNodes = array_merge($constTypeNodes, self::getConstantTypeNodes($arrayShapeItemNode->valueType));
			}
			return $constTypeNodes;
		}

		if (
			$typeNode instanceof UnionTypeNode
			|| $typeNode instanceof IntersectionTypeNode
		) {
			$constTypeNodes = [];
			foreach ($typeNode->types as $innerTypeNode) {
				$constTypeNodes = array_merge($constTypeNodes, self::getConstantTypeNodes($innerTypeNode));
			}
			return $constTypeNodes;
		}

		if ($typeNode instanceof GenericTypeNode) {
			$constTypeNodes = [];
			foreach ($typeNode->genericTypes as $innerTypeNode) {
				$constTypeNodes = array_merge($constTypeNodes, self::getConstantTypeNodes($innerTypeNode));
			}
			return $constTypeNodes;
		}

		if ($typeNode instanceof NullableTypeNode) {
			return self::getConstantTypeNodes($typeNode->type);
		}

		if ($typeNode instanceof CallableTypeNode) {
			$constTypeNodes = self::getConstantTypeNodes($typeNode->returnType);
			foreach ($typeNode->parameters as $callableParameterNode) {
				$constTypeNodes = array_merge($constTypeNodes, self::getConstantTypeNodes($callableParameterNode->type));
			}
			return $constTypeNodes;
		}

		if ($typeNode instanceof ConditionalTypeNode) {
			return array_merge(
				self::getConstantTypeNodes($typeNode->subjectType),
				self::getConstantTypeNodes($typeNode->targetType),
				self::getConstantTypeNodes($typeNode->if),
				self::getConstantTypeNodes($typeNode->else)
			);
		}

		if ($typeNode instanceof ConditionalTypeForParameterNode) {
			return array_merge(
				self::getConstantTypeNodes($typeNode->targetType),
				self::getConstantTypeNodes($typeNode->if),
				self::getConstantTypeNodes($typeNode->else)
			);
		}

		if ($typeNode instanceof OffsetAccessTypeNode) {
			return array_merge(
				self::getConstantTypeNodes($typeNode->type),
				self::getConstantTypeNodes($typeNode->offset)
			);
		}

		if (!$typeNode instanceof ConstTypeNode) {
			return [];
		}

		return [$typeNode];
	}

	/**
	 * @return UnionTypeNode[]
	 */
	public static function getUnionTypeNodes(TypeNode $typeNode): array
	{
		if ($typeNode instanceof UnionTypeNode) {
			return [$typeNode];
		}

		if ($typeNode instanceof NullableTypeNode) {
			return self::getUnionTypeNodes($typeNode->type);
		}

		if ($typeNode instanceof ArrayTypeNode) {
			return self::getUnionTypeNodes($typeNode->type);
		}

		if ($typeNode instanceof ArrayShapeNode) {
			$unionTypeNodes = [];
			foreach ($typeNode->items as $arrayShapeItemNode) {
				$unionTypeNodes = array_merge($unionTypeNodes, self::getUnionTypeNodes($arrayShapeItemNode->valueType));
			}
			return $unionTypeNodes;
		}

		if ($typeNode instanceof IntersectionTypeNode) {
			$unionTypeNodes = [];
			foreach ($typeNode->types as $innerTypeNode) {
				$unionTypeNodes = array_merge($unionTypeNodes, self::getUnionTypeNodes($innerTypeNode));
			}
			return $unionTypeNodes;
		}

		if ($typeNode instanceof GenericTypeNode) {
			$unionTypeNodes = [];
			foreach ($typeNode->genericTypes as $innerTypeNode) {
				$unionTypeNodes = array_merge($unionTypeNodes, self::getUnionTypeNodes($innerTypeNode));
			}
			return $unionTypeNodes;
		}

		if ($typeNode instanceof CallableTypeNode) {
			$unionTypeNodes = self::getUnionTypeNodes($typeNode->returnType);
			foreach ($typeNode->parameters as $callableParameterNode) {
				$unionTypeNodes = array_merge($unionTypeNodes, self::getUnionTypeNodes($callableParameterNode->type));
			}
			return $unionTypeNodes;
		}

		if ($typeNode instanceof ConditionalTypeNode) {
			return array_merge(
				self::getUnionTypeNodes($typeNode->subjectType),
				self::getUnionTypeNodes($typeNode->targetType),
				self::getUnionTypeNodes($typeNode->if),
				self::getUnionTypeNodes($typeNode->else)
			);
		}

		if ($typeNode instanceof ConditionalTypeForParameterNode) {
			return array_merge(
				self::getUnionTypeNodes($typeNode->targetType),
				self::getUnionTypeNodes($typeNode->if),
				self::getUnionTypeNodes($typeNode->else)
			);
		}

		if ($typeNode instanceof OffsetAccessTypeNode) {
			return array_merge(
				self::getUnionTypeNodes($typeNode->type),
				self::getUnionTypeNodes($typeNode->offset)
			);
		}

		return [];
	}

	/**
	 * @return ArrayTypeNode[]
	 */
	public static function getArrayTypeNodes(TypeNode $typeNode): array
	{
		if ($typeNode instanceof ArrayTypeNode) {
			return array_merge([$typeNode], self::getArrayTypeNodes($typeNode->type));
		}

		if ($typeNode instanceof ArrayShapeNode) {
			$arrayTypeNodes = [];
			foreach ($typeNode->items as $arrayShapeItemNode) {
				$arrayTypeNodes = array_merge($arrayTypeNodes, self::getArrayTypeNodes($arrayShapeItemNode->valueType));
			}
			return $arrayTypeNodes;
		}

		if ($typeNode instanceof NullableTypeNode) {
			return self::getArrayTypeNodes($typeNode->type);
		}

		if (
			$typeNode instanceof UnionTypeNode
			|| $typeNode instanceof IntersectionTypeNode
		) {
			$arrayTypeNodes = [];
			foreach ($typeNode->types as $innerTypeNode) {
				$arrayTypeNodes = array_merge($arrayTypeNodes, self::getArrayTypeNodes($innerTypeNode));
			}
			return $arrayTypeNodes;
		}

		if ($typeNode instanceof GenericTypeNode) {
			$arrayTypeNodes = [];
			foreach ($typeNode->genericTypes as $innerTypeNode) {
				$arrayTypeNodes = array_merge($arrayTypeNodes, self::getArrayTypeNodes($innerTypeNode));
			}
			return $arrayTypeNodes;
		}

		if ($typeNode instanceof CallableTypeNode) {
			$arrayTypeNodes = self::getArrayTypeNodes($typeNode->returnType);
			foreach ($typeNode->parameters as $callableParameterNode) {
				$arrayTypeNodes = array_merge($arrayTypeNodes, self::getArrayTypeNodes($callableParameterNode->type));
			}
			return $arrayTypeNodes;
		}

		if ($typeNode instanceof ConditionalTypeNode) {
			return array_merge(
				self::getArrayTypeNodes($typeNode->subjectType),
				self::getArrayTypeNodes($typeNode->targetType),
				self::getArrayTypeNodes($typeNode->if),
				self::getArrayTypeNodes($typeNode->else)
			);
		}

		if ($typeNode instanceof ConditionalTypeForParameterNode) {
			return array_merge(
				self::getArrayTypeNodes($typeNode->targetType),
				self::getArrayTypeNodes($typeNode->if),
				self::getArrayTypeNodes($typeNode->else)
			);
		}

		if ($typeNode instanceof OffsetAccessTypeNode) {
			return array_merge(
				self::getArrayTypeNodes($typeNode->type),
				self::getArrayTypeNodes($typeNode->offset)
			);
		}

		return [];
	}

	/**
	 * @param IdentifierTypeNode|ThisTypeNode $typeNode
	 */
	public static function getTypeHintFromNode(TypeNode $typeNode): string
	{
		return $typeNode instanceof ThisTypeNode
			? (string) $typeNode
			: $typeNode->name;
	}

	public static function export(TypeNode $typeNode): string
	{
		$exportedTypeNode = (string) preg_replace(['~\\s*([&|])\\s*~'], '\\1', (string) $typeNode);

		if (
			$typeNode instanceof UnionTypeNode
			|| $typeNode instanceof IntersectionTypeNode
		) {
			$exportedTypeNode = substr($exportedTypeNode, 1, -1);
		}

		if ($typeNode instanceof ArrayTypeNode && $typeNode->type instanceof CallableTypeNode) {
			$exportedTypeNode = sprintf('(%s)[]', substr($exportedTypeNode, 0, -2));
		}

		return $exportedTypeNode;
	}

	public static function change(TypeNode $masterTypeNode, TypeNode $typeNodeToChange, TypeNode $changedTypeNode): TypeNode
	{
		if ($masterTypeNode === $typeNodeToChange) {
			return $changedTypeNode;
		}

		if ($masterTypeNode instanceof UnionTypeNode) {
			$types = [];
			foreach ($masterTypeNode->types as $typeNone) {
				$types[] = self::change($typeNone, $typeNodeToChange, $changedTypeNode);
			}

			return new UnionTypeNode($types);
		}

		if ($masterTypeNode instanceof IntersectionTypeNode) {
			$types = [];
			foreach ($masterTypeNode->types as $typeNone) {
				$types[] = self::change($typeNone, $typeNodeToChange, $changedTypeNode);
			}

			return new IntersectionTypeNode($types);
		}

		if ($masterTypeNode instanceof GenericTypeNode) {
			$genericTypes = [];
			foreach ($masterTypeNode->genericTypes as $genericTypeNode) {
				$genericTypes[] = self::change($genericTypeNode, $typeNodeToChange, $changedTypeNode);
			}

			/** @var IdentifierTypeNode $identificatorTypeNode */
			$identificatorTypeNode = self::change($masterTypeNode->type, $typeNodeToChange, $changedTypeNode);
			return new GenericTypeNode($identificatorTypeNode, $genericTypes);
		}

		if ($masterTypeNode instanceof ArrayTypeNode) {
			return new ArrayTypeNode(self::change($masterTypeNode->type, $typeNodeToChange, $changedTypeNode));
		}

		if ($masterTypeNode instanceof ArrayShapeNode) {
			$arrayShapeItemNodes = [];
			foreach ($masterTypeNode->items as $arrayShapeItemNode) {
				$arrayShapeItemNodes[] = self::change($arrayShapeItemNode, $typeNodeToChange, $changedTypeNode);
			}

			return new ArrayShapeNode($arrayShapeItemNodes);
		}

		if ($masterTypeNode instanceof ArrayShapeItemNode) {
			return new ArrayShapeItemNode(
				$masterTypeNode->keyName,
				$masterTypeNode->optional,
				self::change($masterTypeNode->valueType, $typeNodeToChange, $changedTypeNode)
			);
		}

		if ($masterTypeNode instanceof NullableTypeNode) {
			return new NullableTypeNode(self::change($masterTypeNode->type, $typeNodeToChange, $changedTypeNode));
		}

		if ($masterTypeNode instanceof CallableTypeNode) {
			$callableParameters = [];
			foreach ($masterTypeNode->parameters as $parameterTypeNode) {
				$callableParameters[] = new CallableTypeParameterNode(
					self::change($parameterTypeNode->type, $typeNodeToChange, $changedTypeNode),
					$parameterTypeNode->isReference,
					$parameterTypeNode->isVariadic,
					$parameterTypeNode->parameterName,
					$parameterTypeNode->isOptional
				);
			}

			/** @var IdentifierTypeNode $identificatorTypeNode */
			$identificatorTypeNode = self::change($masterTypeNode->identifier, $typeNodeToChange, $changedTypeNode);
			return new CallableTypeNode(
				$identificatorTypeNode,
				$callableParameters,
				self::change($masterTypeNode->returnType, $typeNodeToChange, $changedTypeNode)
			);
		}

		if ($masterTypeNode instanceof ConditionalTypeNode) {
			return new ConditionalTypeNode(
				self::change($masterTypeNode->subjectType, $typeNodeToChange, $changedTypeNode),
				self::change($masterTypeNode->targetType, $typeNodeToChange, $changedTypeNode),
				self::change($masterTypeNode->if, $typeNodeToChange, $changedTypeNode),
				self::change($masterTypeNode->else, $typeNodeToChange, $changedTypeNode),
				$masterTypeNode->negated
			);
		}

		if ($masterTypeNode instanceof ConditionalTypeForParameterNode) {
			return new ConditionalTypeForParameterNode(
				$masterTypeNode->parameterName,
				self::change($masterTypeNode->targetType, $typeNodeToChange, $changedTypeNode),
				self::change($masterTypeNode->if, $typeNodeToChange, $changedTypeNode),
				self::change($masterTypeNode->else, $typeNodeToChange, $changedTypeNode),
				$masterTypeNode->negated
			);
		}

		if ($masterTypeNode instanceof OffsetAccessTypeNode) {
			return new OffsetAccessTypeNode(
				self::change($masterTypeNode->type, $typeNodeToChange, $changedTypeNode),
				self::change($masterTypeNode->offset, $typeNodeToChange, $changedTypeNode)
			);
		}

		return clone $masterTypeNode;
	}

	public static function containsStaticOrThisType(TypeNode $typeNode): bool
	{
		if ($typeNode instanceof ThisTypeNode) {
			return true;
		}

		if ($typeNode instanceof IdentifierTypeNode) {
			return strtolower($typeNode->name) === 'static';
		}

		if (
			$typeNode instanceof UnionTypeNode
			|| $typeNode instanceof IntersectionTypeNode
		) {
			foreach ($typeNode->types as $innerTypeNode) {
				if (self::containsStaticOrThisType($innerTypeNode)) {
					return true;
				}
			}
		}

		return false;
	}

	public static function containsOneType(TypeNode $typeNode): bool
	{
		if ($typeNode instanceof IdentifierTypeNode) {
			return true;
		}

		if ($typeNode instanceof ThisTypeNode) {
			return true;
		}

		if ($typeNode instanceof GenericTypeNode) {
			return true;
		}

		if ($typeNode instanceof CallableTypeNode) {
			return true;
		}

		if ($typeNode instanceof ArrayShapeNode) {
			return true;
		}

		if ($typeNode instanceof ArrayTypeNode) {
			return true;
		}

		if ($typeNode instanceof ConstTypeNode) {
			if ($typeNode->constExpr instanceof ConstExprIntegerNode) {
				return true;
			}

			if ($typeNode->constExpr instanceof ConstExprFloatNode) {
				return true;
			}

			if ($typeNode->constExpr instanceof ConstExprStringNode) {
				return true;
			}
		}

		return false;
	}

	public static function containsJustTwoTypes(TypeNode $typeNode): bool
	{
		if ($typeNode instanceof NullableTypeNode && self::containsOneType($typeNode->type)) {
			return true;
		}

		if (
			!$typeNode instanceof UnionTypeNode
			&& !$typeNode instanceof IntersectionTypeNode
		) {
			return false;
		}

		return count($typeNode->types) === 2;
	}

	/**
	 * @param array<int, string> $traversableTypeHints
	 */
	public static function containsTraversableType(TypeNode $typeNode, File $phpcsFile, int $pointer, array $traversableTypeHints): bool
	{
		if ($typeNode instanceof GenericTypeNode) {
			return true;
		}

		if ($typeNode instanceof ArrayShapeNode) {
			return true;
		}

		if ($typeNode instanceof ArrayTypeNode) {
			return true;
		}

		if ($typeNode instanceof IdentifierTypeNode) {
			$fullyQualifiedType = TypeHintHelper::getFullyQualifiedTypeHint($phpcsFile, $pointer, $typeNode->name);
			return TypeHintHelper::isTraversableType($fullyQualifiedType, $traversableTypeHints);
		}

		if (
			$typeNode instanceof UnionTypeNode
			|| $typeNode instanceof IntersectionTypeNode
		) {
			foreach ($typeNode->types as $innerTypeNode) {
				if (self::containsTraversableType($innerTypeNode, $phpcsFile, $pointer, $traversableTypeHints)) {
					return true;
				}
			}
		}

		return
			(
				$typeNode instanceof ConditionalTypeNode
				|| $typeNode instanceof ConditionalTypeForParameterNode
			) && (
				self::containsTraversableType($typeNode->if, $phpcsFile, $pointer, $traversableTypeHints)
				|| self::containsTraversableType($typeNode->else, $phpcsFile, $pointer, $traversableTypeHints)
			);
	}

	/**
	 * @param array<int, string> $traversableTypeHints
	 */
	public static function containsItemsSpecificationForTraversable(
		TypeNode $typeNode,
		File $phpcsFile,
		int $pointer,
		array $traversableTypeHints,
		bool $inTraversable = false
	): bool
	{
		if ($typeNode instanceof GenericTypeNode) {
			foreach ($typeNode->genericTypes as $genericType) {
				if (!self::containsItemsSpecificationForTraversable($genericType, $phpcsFile, $pointer, $traversableTypeHints, true)) {
					return false;
				}
			}

			return true;
		}

		if ($typeNode instanceof ArrayShapeNode) {
			foreach ($typeNode->items as $arrayShapeItemNode) {
				if (!self::containsItemsSpecificationForTraversable(
					$arrayShapeItemNode->valueType,
					$phpcsFile,
					$pointer,
					$traversableTypeHints,
					true
				)) {
					return false;
				}
			}

			return true;
		}

		if ($typeNode instanceof NullableTypeNode) {
			return self::containsItemsSpecificationForTraversable($typeNode->type, $phpcsFile, $pointer, $traversableTypeHints, true);
		}

		if ($typeNode instanceof IdentifierTypeNode) {
			if (TypeHintHelper::isTypeDefinedInAnnotation($phpcsFile, $pointer, $typeNode->name)) {
				// We can expect it's better type for traversable
				return true;
			}

			if (!$inTraversable) {
				return false;
			}

			return !TypeHintHelper::isTraversableType(
				TypeHintHelper::getFullyQualifiedTypeHint($phpcsFile, $pointer, $typeNode->name),
				$traversableTypeHints
			);
		}

		if ($typeNode instanceof ConstTypeNode) {
			return $inTraversable;
		}

		if ($typeNode instanceof CallableTypeNode) {
			return $inTraversable;
		}

		if ($typeNode instanceof ArrayTypeNode) {
			return self::containsItemsSpecificationForTraversable($typeNode->type, $phpcsFile, $pointer, $traversableTypeHints, true);
		}

		if (
			$typeNode instanceof UnionTypeNode
			|| $typeNode instanceof IntersectionTypeNode
		) {
			foreach ($typeNode->types as $innerTypeNode) {
				if (
					!$inTraversable
					&& $innerTypeNode instanceof IdentifierTypeNode
					&& strtolower($innerTypeNode->name) === 'null'
				) {
					continue;
				}

				if (self::containsItemsSpecificationForTraversable(
					$innerTypeNode,
					$phpcsFile,
					$pointer,
					$traversableTypeHints,
					$inTraversable
				)) {
					return true;
				}
			}
		}

		if ($typeNode instanceof ConditionalTypeNode || $typeNode instanceof ConditionalTypeForParameterNode) {
			return
				self::containsItemsSpecificationForTraversable($typeNode->if, $phpcsFile, $pointer, $traversableTypeHints, $inTraversable)
				|| self::containsItemsSpecificationForTraversable(
					$typeNode->else,
					$phpcsFile,
					$pointer,
					$traversableTypeHints,
					$inTraversable
				);
		}

		return false;
	}

	/**
	 * @param CallableTypeNode|GenericTypeNode|IdentifierTypeNode|ThisTypeNode|ArrayTypeNode|ArrayShapeNode|ConstTypeNode $typeNode
	 */
	public static function getTypeHintFromOneType(TypeNode $typeNode, bool $enableUnionTypeHint = false): string
	{
		if ($typeNode instanceof GenericTypeNode) {
			return $typeNode->type->name;
		}

		if ($typeNode instanceof IdentifierTypeNode) {
			if (strtolower($typeNode->name) === 'true') {
				return 'bool';
			}

			if (strtolower($typeNode->name) === 'false') {
				return $enableUnionTypeHint ? 'false' : 'bool';
			}

			if (in_array(strtolower($typeNode->name), ['class-string', 'trait-string', 'callable-string', 'numeric-string'], true)) {
				return 'string';
			}

			return $typeNode->name;
		}

		if ($typeNode instanceof CallableTypeNode) {
			return $typeNode->identifier->name;
		}

		if ($typeNode instanceof ArrayTypeNode) {
			return 'array';
		}

		if ($typeNode instanceof ArrayShapeNode) {
			return 'array';
		}

		if ($typeNode instanceof ConstTypeNode) {
			if ($typeNode->constExpr instanceof ConstExprIntegerNode) {
				return 'int';
			}

			if ($typeNode->constExpr instanceof ConstExprFloatNode) {
				return 'float';
			}

			if ($typeNode->constExpr instanceof ConstExprStringNode) {
				return 'string';
			}
		}

		return (string) $typeNode;
	}

	/**
	 * @param UnionTypeNode|IntersectionTypeNode $typeNode
	 * @param array<int, string> $traversableTypeHints
	 * @return string[]
	 */
	public static function getTraversableTypeHintsFromType(
		TypeNode $typeNode,
		File $phpcsFile,
		int $pointer,
		array $traversableTypeHints,
		bool $enableUnionTypeHint = false
	): array
	{
		$typeHints = [];

		foreach ($typeNode->types as $type) {
			if (
				$type instanceof GenericTypeNode
				|| $type instanceof ThisTypeNode
				|| $type instanceof IdentifierTypeNode
			) {
				$typeHints[] = self::getTypeHintFromOneType($type);
			}
		}

		if (!$enableUnionTypeHint && count($typeHints) > 1) {
			return [];
		}

		foreach ($typeHints as $typeHint) {
			if (!TypeHintHelper::isTraversableType(
				TypeHintHelper::getFullyQualifiedTypeHint($phpcsFile, $pointer, $typeHint),
				$traversableTypeHints
			)) {
				return [];
			}
		}

		return $typeHints;
	}

	/**
	 * @param UnionTypeNode|IntersectionTypeNode $typeNode
	 */
	public static function getItemsSpecificationTypeFromType(TypeNode $typeNode): ?TypeNode
	{
		foreach ($typeNode->types as $type) {
			if ($type instanceof ArrayTypeNode) {
				return $type;
			}
		}

		return null;
	}

}
