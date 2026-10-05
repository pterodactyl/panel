<?php

declare(strict_types=1);

namespace Rules\Tests\Integration\Data;

final class Target
{
    public string $known = 'value';

    public static string $staticKnown = 'value';

    public function known(): string
    {
        return $this->known;
    }
}

function dynamicAccess(Target $target, string $name): array
{
    $viaProperty = $target->$name; // noDynamicName
    $viaBraces = $target->{$name.'x'}; // noDynamicName
    $viaMethod = $target->$name(); // noDynamicName
    $viaStaticProperty = Target::$$name; // noDynamicName

    $viaCallUserFunc = call_user_func('strrev', $name); // disallowed
    $viaCallUserFuncArray = call_user_func_array('strrev', [$name]); // disallowed

    $callable = [$target, 'known'];
    $viaArrayCall = $callable(); // forbiddenArrayMethodCall

    $property = new \ReflectionProperty(Target::class, 'known');
    $viaReflection = $property->getValue($target); // disallowed

    return [$viaProperty, $viaBraces, $viaMethod, $viaStaticProperty, $viaCallUserFunc, $viaCallUserFuncArray, $viaArrayCall, $viaReflection];
}

function runtimeChecks(mixed $value): bool
{
    return is_string($value) || is_numeric($value); // disallowed x2
}
