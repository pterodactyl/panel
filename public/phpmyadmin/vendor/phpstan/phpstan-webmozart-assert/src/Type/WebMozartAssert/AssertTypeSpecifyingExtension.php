<?php declare(strict_types = 1);

namespace PHPStan\Type\WebMozartAssert;

use ArrayAccess;
use Closure;
use Countable;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Cast\Int_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use ReflectionObject;
use Traversable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_reduce;
use function array_shift;
use function count;
use function is_array;
use function lcfirst;
use function substr;

class AssertTypeSpecifyingExtension implements StaticMethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	/** @var Closure[] */
	private static $resolvers;

	/** @var TypeSpecifier */
	private $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return 'Webmozart\Assert\Assert';
	}

	public function isStaticMethodSupported(
		MethodReflection $staticMethodReflection,
		StaticCall $node,
		TypeSpecifierContext $context
	): bool
	{
		if (substr($staticMethodReflection->getName(), 0, 6) === 'allNot') {
			$methods = [
				'allNotInstanceOf' => 2,
				'allNotNull' => 1,
				'allNotSame' => 2,
			];
			return array_key_exists($staticMethodReflection->getName(), $methods)
				&& count($node->getArgs()) >= $methods[$staticMethodReflection->getName()];
		}

		$trimmedName = self::trimName($staticMethodReflection->getName());
		$resolvers = self::getExpressionResolvers();

		if (!array_key_exists($trimmedName, $resolvers)) {
			return false;
		}

		$resolver = $resolvers[$trimmedName];
		$resolverReflection = new ReflectionObject(Closure::fromCallable($resolver));

		return count($node->getArgs()) >= count($resolverReflection->getMethod('__invoke')->getParameters()) - 1;
	}

	private static function trimName(string $name): string
	{
		if (substr($name, 0, 9) === 'allNullOr') {
			$name = substr($name, 9);
		} elseif (substr($name, 0, 6) === 'nullOr') {
			$name = substr($name, 6);
		} elseif (substr($name, 0, 3) === 'all') {
			$name = substr($name, 3);
		}

		return lcfirst($name);
	}

	public function specifyTypes(
		MethodReflection $staticMethodReflection,
		StaticCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes
	{
		if (substr($staticMethodReflection->getName(), 0, 9) === 'allNullOr') {
			return $this->handleAll(
				$staticMethodReflection->getName(),
				$node,
				$scope,
				static function (Type $type) {
					return TypeCombinator::addNull($type);
				}
			);
		}

		if (substr($staticMethodReflection->getName(), 0, 6) === 'allNot') {
			return $this->handleAllNot(
				$staticMethodReflection->getName(),
				$node,
				$scope
			);
		}

		if (substr($staticMethodReflection->getName(), 0, 3) === 'all') {
			return $this->handleAll(
				$staticMethodReflection->getName(),
				$node,
				$scope
			);
		}

		[$expr, $rootExpr] = self::createExpression($scope, $staticMethodReflection->getName(), $node->getArgs());
		if ($expr === null) {
			return new SpecifiedTypes([], []);
		}

		$specifiedTypes = $this->typeSpecifier->specifyTypesInCondition(
			$scope,
			$expr,
			TypeSpecifierContext::createTruthy(),
			$rootExpr
		);

		return $this->specifyRootExprIfSet($rootExpr, $specifiedTypes);
	}

	/**
	 * @param Arg[] $args
	 * @return array{?Expr, ?Expr}
	 */
	private static function createExpression(
		Scope $scope,
		string $name,
		array $args
	): array
	{
		$trimmedName = self::trimName($name);
		$resolvers = self::getExpressionResolvers();
		$resolver = $resolvers[$trimmedName];

		$resolverResult = $resolver($scope, ...$args);
		if (is_array($resolverResult)) {
			[$expr, $rootExpr] = $resolverResult;
		} else {
			$expr = $resolverResult;
			$rootExpr = null;
		}

		if ($expr === null) {
			return [null, null];
		}

		if (substr($name, 0, 6) === 'nullOr') {
			$expr = new BooleanOr(
				$expr,
				new Identical(
					$args[0]->value,
					new ConstFetch(new Name('null'))
				)
			);
		}

		return [$expr, $rootExpr];
	}

	/**
	 * @return array<string, callable(Scope, Arg...): (Expr|array{?Expr, ?Expr}|null)>
	 */
	private static function getExpressionResolvers(): array
	{
		if (self::$resolvers === null) {
			self::$resolvers = [
				'integer' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_int'),
						[$value]
					);
				},
				'positiveInteger' => static function (Scope $scope, Arg $value): Expr {
					return new BooleanAnd(
						new FuncCall(
							new Name('is_int'),
							[$value]
						),
						new Greater(
							$value->value,
							new LNumber(0)
						)
					);
				},
				'string' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_string'),
						[$value]
					);
				},
				'stringNotEmpty' => static function (Scope $scope, Arg $value): Expr {
					return new BooleanAnd(
						new FuncCall(
							new Name('is_string'),
							[$value]
						),
						new NotIdentical(
							$value->value,
							new String_('')
						)
					);
				},
				'float' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_float'),
						[$value]
					);
				},
				'integerish' => static function (Scope $scope, Arg $value): Expr {
					return new BooleanAnd(
						new FuncCall(
							new Name('is_numeric'),
							[$value]
						),
						new Equal(
							$value->value,
							new Int_(
								$value->value
							)
						)
					);
				},
				'numeric' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_numeric'),
						[$value]
					);
				},
				'natural' => static function (Scope $scope, Arg $value): Expr {
					return new BooleanAnd(
						new FuncCall(
							new Name('is_int'),
							[$value]
						),
						new GreaterOrEqual(
							$value->value,
							new LNumber(0)
						)
					);
				},
				'boolean' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_bool'),
						[$value]
					);
				},
				'scalar' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_scalar'),
						[$value]
					);
				},
				'object' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_object'),
						[$value]
					);
				},
				'resource' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_resource'),
						[$value]
					);
				},
				'isCallable' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_callable'),
						[$value]
					);
				},
				'isArray' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_array'),
						[$value]
					);
				},
				'isTraversable' => static function (Scope $scope, Arg $value): Expr {
					return self::$resolvers['isIterable']($scope, $value);
				},
				'isIterable' => static function (Scope $scope, Arg $expr): Expr {
					return new BooleanOr(
						new FuncCall(
							new Name('is_array'),
							[$expr]
						),
						new Instanceof_(
							$expr->value,
							new Name(Traversable::class)
						)
					);
				},
				'isList' => static function (Scope $scope, Arg $expr): Expr {
					return new BooleanAnd(
						new FuncCall(
							new Name('is_array'),
							[$expr]
						),
						new Identical(
							$expr->value,
							new FuncCall(
								new Name('array_values'),
								[$expr]
							)
						)
					);
				},
				'isNonEmptyList' => static function (Scope $scope, Arg $expr): Expr {
					return new BooleanAnd(
						self::$resolvers['isList']($scope, $expr),
						new NotIdentical(
							$expr->value,
							new Array_()
						)
					);
				},
				'isMap' => static function (Scope $scope, Arg $expr): Expr {
					return new BooleanAnd(
						new FuncCall(
							new Name('is_array'),
							[$expr]
						),
						new Identical(
							new FuncCall(
								new Name('array_filter'),
								[$expr, new Arg(new String_('is_string')), new Arg(new ConstFetch(new Name('ARRAY_FILTER_USE_KEY')))]
							),
							$expr->value
						)
					);
				},
				'isNonEmptyMap' => static function (Scope $scope, Arg $expr): Expr {
					return new BooleanAnd(
						self::$resolvers['isMap']($scope, $expr),
						new NotIdentical(
							$expr->value,
							new Array_()
						)
					);
				},
				'isCountable' => static function (Scope $scope, Arg $expr): Expr {
					return new BooleanOr(
						new FuncCall(
							new Name('is_array'),
							[$expr]
						),
						new Instanceof_(
							$expr->value,
							new Name(Countable::class)
						)
					);
				},
				'isInstanceOf' => static function (Scope $scope, Arg $expr, Arg $class): ?Expr {
					$classType = $scope->getType($class->value);
					if ($classType instanceof ConstantStringType) {
						$className = new Name($classType->getValue());
					} elseif ($classType instanceof TypeWithClassName) {
						$className = new Name($classType->getClassName());
					} else {
						return null;
					}

					return new Instanceof_(
						$expr->value,
						$className
					);
				},
				'isInstanceOfAny' => static function (Scope $scope, Arg $expr, Arg $classes): ?Expr {
					return self::buildAnyOfExpr($scope, $expr, $classes, self::$resolvers['isInstanceOf']);
				},
				'notInstanceOf' => static function (Scope $scope, Arg $expr, Arg $class): ?Expr {
					$expr = self::$resolvers['isInstanceOf']($scope, $expr, $class);
					if ($expr === null) {
						return null;
					}

					return new BooleanNot($expr);
				},
				'isAOf' => static function (Scope $scope, Arg $expr, Arg $class): Expr {
					$exprType = $scope->getType($expr->value);
					$allowString = (new StringType())->isSuperTypeOf($exprType)->yes();

					return new FuncCall(
						new Name('is_a'),
						[$expr, $class, new Arg(new ConstFetch(new Name($allowString ? 'true' : 'false')))]
					);
				},
				'isAnyOf' => static function (Scope $scope, Arg $value, Arg $classes): ?Expr {
					return self::buildAnyOfExpr($scope, $value, $classes, self::$resolvers['isAOf']);
				},
				'isNotA' => static function (Scope $scope, Arg $value, Arg $class): Expr {
					return new BooleanNot(self::$resolvers['isAOf']($scope, $value, $class));
				},
				'implementsInterface' => static function (Scope $scope, Arg $expr, Arg $class): ?Expr {
					$classType = $scope->getType($class->value);
					if (!$classType instanceof ConstantStringType) {
						return null;
					}

					$classReflection = (new ObjectType($classType->getValue()))->getClassReflection();
					if ($classReflection === null) {
						return null;
					}

					if (!$classReflection->isInterface()) {
						return new ConstFetch(new Name('false'));
					}

					return self::$resolvers['subclassOf']($scope, $expr, $class);
				},
				'keyExists' => static function (Scope $scope, Arg $array, Arg $key): Expr {
					return new FuncCall(
						new Name('array_key_exists'),
						[$key, $array]
					);
				},
				'keyNotExists' => static function (Scope $scope, Arg $array, Arg $key): Expr {
					return new BooleanNot(self::$resolvers['keyExists']($scope, $array, $key));
				},
				'validArrayKey' => static function (Scope $scope, Arg $value): Expr {
					return new BooleanOr(
						new FuncCall(
							new Name('is_int'),
							[$value]
						),
						new FuncCall(
							new Name('is_string'),
							[$value]
						)
					);
				},
				'true' => static function (Scope $scope, Arg $expr): Expr {
					return new Identical(
						$expr->value,
						new ConstFetch(new Name('true'))
					);
				},
				'false' => static function (Scope $scope, Arg $expr): Expr {
					return new Identical(
						$expr->value,
						new ConstFetch(new Name('false'))
					);
				},
				'null' => static function (Scope $scope, Arg $expr): Expr {
					return new Identical(
						$expr->value,
						new ConstFetch(new Name('null'))
					);
				},
				'notFalse' => static function (Scope $scope, Arg $expr): Expr {
					return new NotIdentical(
						$expr->value,
						new ConstFetch(new Name('false'))
					);
				},
				'notNull' => static function (Scope $scope, Arg $expr): Expr {
					return new NotIdentical(
						$expr->value,
						new ConstFetch(new Name('null'))
					);
				},
				'eq' => static function (Scope $scope, Arg $value, Arg $value2): Expr {
					return new Equal(
						$value->value,
						$value2->value
					);
				},
				'notEq' => static function (Scope $scope, Arg $value, Arg $value2): Expr {
					return new BooleanNot(self::$resolvers['eq']($scope, $value, $value2));
				},
				'same' => static function (Scope $scope, Arg $value1, Arg $value2): Expr {
					return new Identical(
						$value1->value,
						$value2->value
					);
				},
				'notSame' => static function (Scope $scope, Arg $value1, Arg $value2): Expr {
					return new NotIdentical(
						$value1->value,
						$value2->value
					);
				},
				'greaterThan' => static function (Scope $scope, Arg $value, Arg $limit): Expr {
					return new Greater(
						$value->value,
						$limit->value
					);
				},
				'greaterThanEq' => static function (Scope $scope, Arg $value, Arg $limit): Expr {
					return new GreaterOrEqual(
						$value->value,
						$limit->value
					);
				},
				'lessThan' => static function (Scope $scope, Arg $value, Arg $limit): Expr {
					return new Smaller(
						$value->value,
						$limit->value
					);
				},
				'lessThanEq' => static function (Scope $scope, Arg $value, Arg $limit): Expr {
					return new SmallerOrEqual(
						$value->value,
						$limit->value
					);
				},
				'range' => static function (Scope $scope, Arg $value, Arg $min, Arg $max): Expr {
					return new BooleanAnd(
						new GreaterOrEqual(
							$value->value,
							$min->value
						),
						new SmallerOrEqual(
							$value->value,
							$max->value
						)
					);
				},
				'subclassOf' => static function (Scope $scope, Arg $expr, Arg $class): Expr {
					return new FuncCall(
						new Name('is_subclass_of'),
						[
							new Arg($expr->value),
							$class,
						]
					);
				},
				'classExists' => static function (Scope $scope, Arg $class): Expr {
					return new FuncCall(
						new Name('class_exists'),
						[$class]
					);
				},
				'interfaceExists' => static function (Scope $scope, Arg $class): Expr {
					return new FuncCall(
						new Name('interface_exists'),
						[$class]
					);
				},
				'count' => static function (Scope $scope, Arg $array, Arg $number): Expr {
					return new Identical(
						new FuncCall(
							new Name('count'),
							[$array]
						),
						$number->value
					);
				},
				'minCount' => static function (Scope $scope, Arg $array, Arg $min): Expr {
					return new GreaterOrEqual(
						new FuncCall(
							new Name('count'),
							[$array]
						),
						$min->value
					);
				},
				'maxCount' => static function (Scope $scope, Arg $array, Arg $max): Expr {
					return new SmallerOrEqual(
						new FuncCall(
							new Name('count'),
							[$array]
						),
						$max->value
					);
				},
				'countBetween' => static function (Scope $scope, Arg $array, Arg $min, Arg $max): Expr {
					return new BooleanAnd(
						new GreaterOrEqual(
							new FuncCall(
								new Name('count'),
								[$array]
							),
							$min->value
						),
						new SmallerOrEqual(
							new FuncCall(
								new Name('count'),
								[$array]
							),
							$max->value
						)
					);
				},
				'length' => static function (Scope $scope, Arg $value, Arg $length): Expr {
					return new BooleanAnd(
						new FuncCall(
							new Name('is_string'),
							[$value]
						),
						new Identical(
							new FuncCall(
								new Name('strlen'),
								[$value]
							),
							$length->value
						)
					);
				},
				'minLength' => static function (Scope $scope, Arg $value, Arg $min): Expr {
					return new BooleanAnd(
						new FuncCall(
							new Name('is_string'),
							[$value]
						),
						new GreaterOrEqual(
							new FuncCall(
								new Name('strlen'),
								[$value]
							),
							$min->value
						)
					);
				},
				'maxLength' => static function (Scope $scope, Arg $value, Arg $max): Expr {
					return new BooleanAnd(
						new FuncCall(
							new Name('is_string'),
							[$value]
						),
						new SmallerOrEqual(
							new FuncCall(
								new Name('strlen'),
								[$value]
							),
							$max->value
						)
					);
				},
				'lengthBetween' => static function (Scope $scope, Arg $value, Arg $min, Arg $max): Expr {
					return new BooleanAnd(
						new FuncCall(
							new Name('is_string'),
							[$value]
						),
						new BooleanAnd(
							new GreaterOrEqual(
								new FuncCall(
									new Name('strlen'),
									[$value]
								),
								$min->value
							),
							new SmallerOrEqual(
								new FuncCall(
									new Name('strlen'),
									[$value]
								),
								$max->value
							)
						)
					);
				},
				'inArray' => static function (Scope $scope, Arg $needle, Arg $array): Expr {
					return new FuncCall(
						new Name('in_array'),
						[
							$needle,
							$array,
							new Arg(new ConstFetch(new Name('true'))),
						]
					);
				},
				'oneOf' => static function (Scope $scope, Arg $needle, Arg $array): Expr {
					return self::$resolvers['inArray']($scope, $needle, $array);
				},
				'methodExists' => static function (Scope $scope, Arg $object, Arg $method): Expr {
					return new FuncCall(
						new Name('method_exists'),
						[$object, $method]
					);
				},
				'propertyExists' => static function (Scope $scope, Arg $object, Arg $property): Expr {
					return new FuncCall(
						new Name('property_exists'),
						[$object, $property]
					);
				},
				'isArrayAccessible' => static function (Scope $scope, Arg $expr): Expr {
					return new BooleanOr(
						new FuncCall(
							new Name('is_array'),
							[$expr]
						),
						new Instanceof_(
							$expr->value,
							new Name(ArrayAccess::class)
						)
					);
				},
			];

			foreach (['contains', 'startsWith', 'endsWith'] as $name) {
				self::$resolvers[$name] = static function (Scope $scope, Arg $value, Arg $subString) use ($name): array {
					if ($scope->getType($subString->value)->isNonEmptyString()->yes()) {
						return self::createIsNonEmptyStringAndSomethingExprPair($name, [$value, $subString]);
					}

					return [self::$resolvers['string']($scope, $value), null];
				};
			}

			$assertionsResultingAtLeastInNonEmptyString = [
				'startsWithLetter',
				'unicodeLetters',
				'alpha',
				'digits',
				'alnum',
				'lower',
				'upper',
				'uuid',
				'ip',
				'ipv4',
				'ipv6',
				'email',
				'notWhitespaceOnly',
			];
			foreach ($assertionsResultingAtLeastInNonEmptyString as $name) {
				self::$resolvers[$name] = static function (Scope $scope, Arg $value) use ($name): array {
					return self::createIsNonEmptyStringAndSomethingExprPair($name, [$value]);
				};
			}

		}

		return self::$resolvers;
	}

	private function handleAllNot(
		string $methodName,
		StaticCall $node,
		Scope $scope
	): SpecifiedTypes
	{
		if ($methodName === 'allNotNull') {
			return $this->allArrayOrIterable(
				$scope,
				$node->getArgs()[0]->value,
				static function (Type $type): Type {
					return TypeCombinator::removeNull($type);
				}
			);
		}

		if ($methodName === 'allNotInstanceOf') {
			$classType = $scope->getType($node->getArgs()[1]->value);

			if ($classType instanceof ConstantStringType) {
				$objectType = new ObjectType($classType->getValue());
			} elseif ($classType instanceof TypeWithClassName) {
				$objectType = new ObjectType($classType->getClassName());
			} else {
				return new SpecifiedTypes([], []);
			}

			return $this->allArrayOrIterable(
				$scope,
				$node->getArgs()[0]->value,
				static function (Type $type) use ($objectType): Type {
					return TypeCombinator::remove($type, $objectType);
				}
			);
		}

		if ($methodName === 'allNotSame') {
			$valueType = $scope->getType($node->getArgs()[1]->value);
			return $this->allArrayOrIterable(
				$scope,
				$node->getArgs()[0]->value,
				static function (Type $type) use ($valueType): Type {
					return TypeCombinator::remove($type, $valueType);
				}
			);
		}

		throw new ShouldNotHappenException();
	}

	/**
	 * @param callable(Type): Type|null $typeModifier
	 */
	private function handleAll(
		string $methodName,
		StaticCall $node,
		Scope $scope,
		?callable $typeModifier = null
	): SpecifiedTypes
	{
		$args = $node->getArgs();
		$args[0] = new Arg(new ArrayDimFetch($args[0]->value, new LNumber(0)));
		[$expr, $rootExpr] = self::createExpression($scope, $methodName, $args);
		if ($expr === null) {
			return new SpecifiedTypes();
		}

		$specifiedTypes = $this->typeSpecifier->specifyTypesInCondition(
			$scope,
			$expr,
			TypeSpecifierContext::createTruthy(),
			$rootExpr
		);

		$sureNotTypes = $specifiedTypes->getSureNotTypes();
		foreach ($specifiedTypes->getSureTypes() as $exprStr => [$exprNode, $type]) {
			if ($exprNode !== $args[0]->value) {
				continue;
			}

			$type = TypeCombinator::remove($type, $sureNotTypes[$exprStr][1] ?? new NeverType());
			if ($typeModifier !== null) {
				$type = $typeModifier($type);
			}

			return $this->allArrayOrIterable(
				$scope,
				$node->getArgs()[0]->value,
				static function () use ($type): Type {
					return $type;
				},
				$rootExpr
			);
		}

		return $specifiedTypes;
	}

	private function allArrayOrIterable(
		Scope $scope,
		Expr $expr,
		Closure $typeCallback,
		?Expr $rootExpr = null
	): SpecifiedTypes
	{
		$currentType = TypeCombinator::intersect($scope->getType($expr), new IterableType(new MixedType(), new MixedType()));
		$arrayTypes = $currentType->getArrays();
		if (count($arrayTypes) > 0) {
			$newArrayTypes = [];
			foreach ($arrayTypes as $arrayType) {
				if ($arrayType instanceof ConstantArrayType) {
					$builder = ConstantArrayTypeBuilder::createEmpty();
					foreach ($arrayType->getKeyTypes() as $i => $keyType) {
						$valueType = $typeCallback($arrayType->getValueTypes()[$i]);
						if ($valueType instanceof NeverType) {
							continue 2;
						}
						$builder->setOffsetValueType($keyType, $valueType, $arrayType->isOptionalKey($i));
					}
					$newArrayTypes[] = $builder->getArray();
				} else {
					$itemType = $typeCallback($arrayType->getItemType());
					if ($itemType instanceof NeverType) {
						continue;
					}
					$newArrayTypes[] = new ArrayType($arrayType->getKeyType(), $itemType);
				}
			}

			$specifiedType = TypeCombinator::union(...$newArrayTypes);
		} elseif ((new IterableType(new MixedType(), new MixedType()))->isSuperTypeOf($currentType)->yes()) {
			$itemType = $typeCallback($currentType->getIterableValueType());
			if ($itemType instanceof NeverType) {
				$specifiedType = $itemType;
			} else {
				$specifiedType = new IterableType($currentType->getIterableKeyType(), $itemType);
			}
		} else {
			return new SpecifiedTypes([], []);
		}

		$specifiedTypes = $this->typeSpecifier->create(
			$expr,
			$specifiedType,
			TypeSpecifierContext::createTruthy(),
			false,
			$scope,
			$rootExpr
		);

		return $this->specifyRootExprIfSet($rootExpr, $specifiedTypes);
	}

	/**
	 * @param Expr[] $expressions
	 * @param class-string<BinaryOp> $binaryOp
	 */
	private static function implodeExpr(array $expressions, string $binaryOp): ?Expr
	{
		$firstExpression = array_shift($expressions);
		if ($firstExpression === null) {
			return null;
		}

		return array_reduce(
			$expressions,
			static function (Expr $carry, Expr $item) use ($binaryOp) {
				return new $binaryOp($carry, $item);
			},
			$firstExpression
		);
	}

	private static function buildAnyOfExpr(Scope $scope, Arg $value, Arg $items, callable $resolver): ?Expr
	{
		if (!$items->value instanceof Array_ || $items->value->items === null) {
			return null;
		}

		$resolvers = array_map(
			static function (?ArrayItem $item) use ($scope, $value, $resolver) {
				return $item !== null ? $resolver($scope, $value, new Arg($item->value)) : null;
			},
			$items->value->items
		);
		$resolvers = array_filter($resolvers);

		return self::implodeExpr($resolvers, BooleanOr::class);
	}

	/**
	 * @param Arg[] $args
	 * @return array{Expr, Expr}
	 */
	private static function createIsNonEmptyStringAndSomethingExprPair(string $name, array $args): array
	{
		$expr = new BooleanAnd(
			new FuncCall(
				new Name('is_string'),
				[$args[0]]
			),
			new NotIdentical(
				$args[0]->value,
				new String_('')
			)
		);

		$rootExpr = new BooleanAnd(
			$expr,
			new FuncCall(new Name('FAUX_FUNCTION_ ' . $name), $args)
		);

		return [$expr, $rootExpr];
	}

	private function specifyRootExprIfSet(?Expr $rootExpr, SpecifiedTypes $specifiedTypes): SpecifiedTypes
	{
		if ($rootExpr === null) {
			return $specifiedTypes;
		}

		// Makes consecutive calls with a rootExpr adding unknown info via FAUX_FUNCTION evaluate to true
		return $specifiedTypes->unionWith(
			$this->typeSpecifier->create($rootExpr, new ConstantBooleanType(true), TypeSpecifierContext::createTruthy())
		);
	}

}
