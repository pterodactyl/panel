<?php

declare(strict_types=1);

namespace Rules\Support;

use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * Shared classification of the type-evidence escape hatches every rule
 * rejects: explicit `mixed`, bare `object`, `stdClass`, and dictionary
 * value types that resolve to one of those. Uses the covered Type API
 * (is*()/getIterableValueType()/getTemplateType()) rather than instanceof on
 * concrete Type classes, which PHPStan deprecates.
 */
final class TypeClassifier
{
    public function isExplicitMixed(Type $type): bool
    {
        return $type instanceof MixedType && $type->isExplicitMixed();
    }

    public function isNever(Type $type): bool
    {
        return $type instanceof NeverType;
    }

    /**
     * Bare `object`, directly or as a union member (`?object`, `object|null`).
     */
    public function containsBroadObject(Type $type): bool
    {
        foreach ($this->unionMembers($type) as $member) {
            if ($this->isBareObject($member)) {
                return true;
            }
        }

        return false;
    }

    public function containsStdClass(Type $type): bool
    {
        foreach ($this->unionMembers($type) as $member) {
            if (in_array('stdClass', $member->getObjectClassNames(), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Classifies a declared type as an unsafe dictionary: an array, iterable,
     * list, Traversable, or ArrayAccess whose value type is explicit `mixed`,
     * bare `object`, an untyped array, `stdClass`, or a union containing one
     * of those. Constant array shapes (`array{...}`) are safe. Bare `array`
     * with an implicit-mixed value is left to PHPStan level 6.
     *
     * @return string|null the offending value description, or null when safe
     */
    public function unsafeDictionaryValueKind(Type $type): ?string
    {
        foreach ($this->unionMembers($type) as $member) {
            $valueType = $this->dictionaryValueType($member);
            if ($valueType === null) {
                continue;
            }

            $kind = $this->unsafeValueKind($valueType);
            if ($kind !== null) {
                return $kind;
            }
        }

        return null;
    }

    private function dictionaryValueType(Type $type): ?Type
    {
        if ($type->isConstantArray()->yes()) {
            return null;
        }

        if ($type->isArray()->yes() || $type->isIterable()->yes()) {
            return $type->getIterableValueType();
        }

        if (in_array('ArrayAccess', $type->getObjectClassNames(), true)) {
            $valueType = $type->getTemplateType('ArrayAccess', 'TValue');

            return $this->isNever($valueType) ? null : $valueType;
        }

        return null;
    }

    private function unsafeValueKind(Type $valueType): ?string
    {
        foreach ($this->unionMembers($valueType) as $member) {
            if ($this->isExplicitMixed($member)) {
                return 'mixed';
            }

            if ($this->isBareObject($member)) {
                return 'object';
            }

            if (in_array('stdClass', $member->getObjectClassNames(), true)) {
                return 'stdClass';
            }

            $memberValue = $member->isArray()->yes() ? $member->getIterableValueType() : null;
            if ($memberValue instanceof MixedType && ! $memberValue->isExplicitMixed()) {
                return 'untyped array';
            }
        }

        return null;
    }

    private function isBareObject(Type $type): bool
    {
        // A template constrained to object (`@template T of object`) keeps its
        // evidence through the template, mirroring `<T extends object>`.
        return $type->isObject()->yes()
            && $type->getObjectClassNames() === []
            && ! $type instanceof TemplateType;
    }

    /**
     * @return list<Type>
     */
    private function unionMembers(Type $type): array
    {
        if (! $type instanceof UnionType) {
            return [$type];
        }

        $members = [];
        foreach ($type->getTypes() as $member) {
            foreach ($this->unionMembers($member) as $nested) {
                $members[] = $nested;
            }
        }

        return $members;
    }
}
