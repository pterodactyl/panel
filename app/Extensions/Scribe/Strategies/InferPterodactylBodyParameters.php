<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Strategies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Knuckles\Scribe\Extracting\Strategies\BodyParameters\GetFromFormRequest;
use Knuckles\Scribe\Tools\Globals;
use Pterodactyl\Extensions\Scribe\Support\PterodactylDocumentation;
use Pterodactyl\Support\JsonValueGuard;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use Throwable;

class InferPterodactylBodyParameters extends GetFromFormRequest
{
    /**
     * @return ScribeParameters
     */
    public function getParametersFromFormRequest(ReflectionFunctionAbstract $method, Route $route): array
    {
        if (! ($formRequestReflectionClass = $this->getFormRequestReflectionClass($method)) instanceof ReflectionClass) {
            return [];
        }

        if (! $this->isFormRequestMeantForThisStrategy($formRequestReflectionClass)) {
            return [];
        }

        $className = $formRequestReflectionClass->getName();

        $factory = Globals::$__instantiateFormRequestUsing;
        if (is_callable($factory)) {
            $formRequest = $factory($className, $route, $method);
        } else {
            $formRequest = new $className;
        }

        if (! $formRequest instanceof FormRequest) {
            return [];
        }

        $formRequest->setRouteResolver(function () use ($formRequest, $method, $route) {
            $boundRoute = $route->bind($formRequest);
            $this->bindRouteModelPlaceholders($method, $boundRoute);

            return $boundRoute;
        });
        $formRequest->server->set('REQUEST_METHOD', $route->methods()[0]);

        try {
            $validationRules = $this->getRouteValidationRules($formRequest);
            if (! is_array($validationRules)) {
                return [];
            }

            $parameters = $this->getParametersFromValidationRules(
                $validationRules,
                $this->getCustomParameterData($formRequest)
            );
        } catch (Throwable) {
            return [];
        }

        $normalizable = [];
        foreach ($parameters as $name => $parameter) {
            if (is_array($parameter)) {
                $normalizable[$name] = $parameter;
            }
        }

        $inferred = $this->normaliseArrayAndObjectParameters($normalizable);
        $parameters = [];
        foreach ($inferred as $name => $parameter) {
            if (! is_string($name) || ! is_array($parameter)) {
                continue;
            }

            foreach ($parameter as $key => $value) {
                if ($value instanceof UploadedFile) {
                    $parameter[$key] = $value->getClientOriginalName();
                }
            }

            JsonValueGuard::assertPayload($parameter);
            $parameters[$name] = $parameter;
        }

        foreach ($parameters as $name => $parameter) {
            if ($type = PterodactylDocumentation::fieldType($name)) {
                $parameters[$name]['type'] = $type;
            }

            if ($name === 'options') {
                $parameters[$name]['type'] = 'string[]';
            }

            if ($name === 'order') {
                $parameters[$name]['type'] = 'integer[]';
            }

            $type = $parameters[$name]['type'] ?? 'string';
            if (! is_string($type)) {
                $type = 'string';
                $parameters[$name]['type'] = $type;
            }

            $parameters[$name]['example'] = PterodactylDocumentation::fieldExample($name, $type);
        }

        return $parameters;
    }

    /**
     * @return array<string, array{description: string}>
     */
    protected function getCustomParameterData(FormRequest $formRequest): array
    {
        try {
            $rules = method_exists($formRequest, 'rules') ? app()->call([$formRequest, 'rules']) : [];
        } catch (Throwable) {
            return [];
        }

        if (! is_array($rules)) {
            return [];
        }

        $data = [];

        foreach (array_keys($rules) as $name) {
            // Rule keys are ordinarily field names, but a purely numeric-looking key
            // (e.g. "0") would be cast to an int by PHP's array key coercion -- skip
            // it rather than pretend it is the string field name callers expect.
            if (! is_string($name) || str_contains($name, '*')) {
                continue;
            }

            $data[$name] = [
                'description' => PterodactylDocumentation::fieldDescription($name),
            ];
        }

        return $data;
    }

    private function bindRouteModelPlaceholders(ReflectionFunctionAbstract $method, Route $route): void
    {
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType || ! is_subclass_of($type->getName(), Model::class)) {
                continue;
            }

            $modelClass = $type->getName();
            $model = new $modelClass;
            $model->setAttribute($model->getKeyName(), 1);
            $model->exists = true;

            $route->setParameter($parameter->getName(), $model);
        }
    }
}
