<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\OpenApi;

use BackedEnum;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Knuckles\Camel\Extraction\Response as ExtractedResponse;
use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Attributes\ResponseFromTransformer;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\OpenApiGenerator;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Extensions\Scribe\Support\PterodactylDocumentation;
use Pterodactyl\Models\Permission;
use Pterodactyl\Services\Extensions\ExtensionPermissionRegistry;
use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Support\JsonValueGuard;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use stdClass;
use UnexpectedValueException;
use UnitEnum;

class PterodactylOpenApiGenerator extends OpenApiGenerator
{
    /** @var array<string, string>|null */
    private ?array $operationIds = null;

    /** @var array<string, class-string>|null */
    private ?array $transformerClasses = null;

    /** @var OpenApiSchemaMap */
    private array $requestBodySchemas = [];

    /** @var OpenApiSchemaMap */
    private array $responseBodySchemas = [];

    /** @var OpenApiSchemaMap */
    private array $parameterSchemas = [];

    /** @var array<string, true> */
    private array $operationSchemaRefs = [];

    /**
     * @param  OpenApiRoot  $root
     * @return OpenApiRoot
     */
    public function root(array $root, array $groupedEndpoints): array
    {
        $root['components']['securitySchemes']['panelSession'] = [
            'type' => 'apiKey',
            'in' => 'cookie',
            'name' => JsonValueGuard::string(config('session.cookie', 'pterodactyl_session')),
            'description' => 'Browser session cookie used by the panel frontend.',
        ];

        foreach ($this->componentSchemas($groupedEndpoints) as $name => $schema) {
            $root['components']['schemas'][$name] = $schema;
        }

        foreach ($this->requestBodySchemas as $name => $schema) {
            $root['components']['schemas'][$name] = $schema;
        }

        foreach ($this->responseBodySchemas as $name => $schema) {
            $root['components']['schemas'][$name] = $schema;
        }

        foreach ($this->parameterSchemas as $name => $schema) {
            $root['components']['schemas'][$name] = $schema;
        }

        $this->normalizeRootSchemas($root);
        $this->pruneUnreferencedSchemas($root);

        return $root;
    }

    /**
     * @param  OpenApiOperation  $pathItem
     * @return OpenApiOperation
     */
    public function pathItem(array $pathItem, array $groupedEndpoints, OutputEndpointData $endpoint): array
    {
        $pathItem['operationId'] = $this->operationIds($groupedEndpoints)[$this->endpointKey($endpoint)]
            ?? $this->baseOperationId($endpoint);

        $pathItem = $this->normalizeBrowserSessionOperation($pathItem, $endpoint);
        $this->enforceKnownBodyParameterSchemas($pathItem, $endpoint);
        $pathItem = $this->normalizeOperationRequestBody($pathItem, $endpoint);
        $this->stripEmptySuccessResponseContent($pathItem);
        $this->replaceSuccessResponseSchema($pathItem, $endpoint);
        $this->replacePlainSuccessResponseSchema($pathItem);
        $this->replaceErrorResponseSchemas($pathItem);
        $pathItem = $this->normalizeSchemas($pathItem);
        $this->applyResponseFieldsToInlineSchemas($pathItem, $endpoint);
        $this->normalizeRawResponseMediaTypes($pathItem, $endpoint);
        $this->extractRequestBodySchemas($pathItem);
        $this->extractResponseBodySchemas($pathItem);
        $this->extractOperationParameterSchemas($pathItem);
        JsonValueGuard::assertValue($pathItem);
        $this->collectComponentRefs($pathItem, $this->operationSchemaRefs);

        return $pathItem;
    }

    /**
     * @param  list<ScribeParameter>  $parameters
     * @return list<ScribeParameter>
     */
    public function pathParameters(array $parameters, array $endpoints, array $urlParameters): array
    {
        $endpoint = $endpoints[0] ?? null;
        if (! $endpoint instanceof OutputEndpointData) {
            return $parameters;
        }

        foreach ($parameters as &$parameter) {
            $this->extractParameterSchema($parameter, $this->pathParameterSchemaComponentName($endpoint, $parameter));
        }

        unset($parameter);

        JsonValueGuard::assertValue($parameters);
        $this->collectComponentRefs($parameters, $this->operationSchemaRefs);

        return $parameters;
    }

    /**
     * @param  array<int, array{description: string, name: string, endpoints: OutputEndpointData[]}>  $groupedEndpoints
     * @return OpenApiSchemaMap
     */
    private function componentSchemas(array $groupedEndpoints): array
    {
        $schemas = [
            'AdminEggConfigurationFile' => $this->adminEggConfigurationFileSchema(),
            'AdminEggConfigurationFiles' => $this->adminEggConfigurationFilesSchema(),
            'AdminEggConfigurationFind' => $this->adminEggConfigurationFindSchema(),
            'AdminExtensionSettingField' => $this->adminExtensionSettingFieldSchema(),
            'ExtensionFields' => $this->extensionFieldsSchema(),
            'AdminExtensionSettingOption' => $this->adminExtensionSettingOptionSchema(),
            'AdminLanguagesResponse' => $this->adminLanguagesSchema(),
            'AdminNodeConfigurationResponse' => $this->adminNodeConfigurationSchema(),
            'AdminNodeDeployTokenResponse' => $this->adminNodeDeployTokenSchema(),
            'AdminNodeSystemInformationResponse' => $this->adminNodeSystemInformationSchema(),
            'AdminNodeUtilizationResponse' => $this->adminNodeUtilizationSchema(),
            'AdminServerVariableAttributes' => $this->adminServerVariableAttributesSchema(),
            'AdminServerVariableListResponse' => $this->fractalListSchema('AdminServerVariableResource'),
            'AdminServerVariablePaginatedResponse' => $this->fractalPaginatedSchema('AdminServerVariableResource'),
            'AdminServerVariableResource' => $this->fractalItemSchema('server_variable', 'AdminServerVariableAttributes'),
            'AdminSettingsResponse' => $this->adminSettingsSchema(),
            'AdminVersionInformationResponse' => $this->adminVersionInformationSchema(),
            'AuthLoginCheckpointResponse' => $this->authLoginCheckpointResponseSchema(),
            'AuthLoginCompleteResponse' => $this->authLoginCompleteResponseSchema(),
            'AuthLoginResponse' => $this->authLoginResponseSchema(),
            'AuthPasswordResetEmailResponse' => $this->authPasswordResetEmailResponseSchema(),
            'AuthResetPasswordResponse' => $this->authResetPasswordResponseSchema(),
            'AuthUser' => $this->authUserSchema(),
            'ClientSystemPermissionsResponse' => $this->clientSystemPermissionsSchema(),
            'ClientTwoFactorSetupResponse' => $this->clientTwoFactorSetupSchema(),
            'ErrorEnvelope' => $this->errorEnvelopeSchema(false),
            'NullResource' => $this->nullResourceSchema(),
            'ValidationErrorEnvelope' => $this->errorEnvelopeSchema(true),
            'PaginationMeta' => $this->paginationMetaSchema(),
        ];

        $knownComponents = [
            'AdminServerVariableListResponse' => true,
            'AdminServerVariableResource' => true,
        ];
        foreach ($groupedEndpoints as $group) {
            foreach ($group['endpoints'] as $endpoint) {
                $fractal = $this->fractalResponse($endpoint);
                if ($fractal === null) {
                    continue;
                }

                foreach ($this->fractalComponents($endpoint, $fractal['resource']) as $component) {
                    $knownComponents[$component] = true;
                }
            }
        }

        foreach ($groupedEndpoints as $group) {
            foreach ($group['endpoints'] as $endpoint) {
                $fractal = $this->fractalResponse($endpoint);
                if ($fractal === null) {
                    continue;
                }

                $components = $this->fractalComponents($endpoint, $fractal['resource']);
                $attributes = $components['attributes'];
                if (! isset($schemas[$attributes])) {
                    $attributeValues = $fractal['attributes'];
                    if (str_ends_with($attributes, 'EggAttributes') && is_array($attributeValues['config'] ?? null)) {
                        foreach (['files', 'startup', 'logs', 'file_denylist'] as $declaredField) {
                            $attributeValues['config'][$declaredField] = [];
                        }
                    }

                    try {
                        $schemas[$attributes] = PterodactylDocumentation::schemaForValue($attributeValues);
                    } catch (UnexpectedValueException $exception) {
                        throw new UnexpectedValueException("Unable to infer the {$attributes} schema for {$endpoint->uri}.", $exception->getCode(), previous: $exception);
                    }
                }

                JsonValueGuard::assertOpenApiSchemaInput($schemas[$attributes]);
                $this->applyDeclaredFieldMetadata($schemas[$attributes], $endpoint);
                if (isset($fractal['attributes']['extensions'])) {
                    JsonValueGuard::assertOpenApiSchemaInput($schemas[$attributes]);
                    $this->addExtensionFieldsToAttributesSchema($schemas[$attributes]);
                }

                // The valid subuser permission values come from the runtime permission
                // registry, so they cannot be declared as a constant attribute.
                if ($attributes === 'ClientServerSubuserAttributes') {
                    $properties = $schemas[$attributes]['properties'] ?? null;
                    if (is_array($properties) && isset($properties['permissions'])) {
                        JsonValueGuard::assertOpenApiSchemaMap($properties);
                        $properties['permissions'] = $this->permissionsSchema();
                        $schemas[$attributes]['properties'] = $properties;
                    }
                }

                if (isset($fractal['relationships'])) {
                    JsonValueGuard::assertOpenApiSchemaInput($schemas[$attributes]);
                    $this->addRelationshipsToAttributesSchema($schemas[$attributes], $endpoint, $fractal['relationships'], $knownComponents);
                }

                $schemas[$components['item']] ??= $this->fractalItemSchema($fractal['resource'], $attributes);
                if (isset($fractal['meta'])) {
                    JsonValueGuard::assertOpenApiSchemaInput($schemas[$components['item']]);
                    $this->addMetaToItemSchema($schemas[$components['item']], $fractal['meta']);
                }

                $schemas[$components['list']] ??= $this->fractalListSchema($components['item']);
                $schemas[$components['page']] ??= $this->fractalPaginatedSchema($components['item']);
                if (isset($fractal['top_meta'])) {
                    JsonValueGuard::assertOpenApiSchemaInput($schemas[$components['list']]);
                    JsonValueGuard::assertOpenApiSchemaInput($schemas[$components['page']]);
                    $this->addMetaToListSchema($schemas[$components['list']], $fractal['top_meta']);
                    JsonValueGuard::assertOpenApiSchemaInput($schemas[$components['page']]);
                    $this->addMetaToPaginatedSchema($schemas[$components['page']], $fractal['top_meta']);
                }
            }
        }

        foreach ($schemas as &$schema) {
            JsonValueGuard::assertPayload9($schema);
        }

        unset($schema);
        ksort($schemas);

        return $schemas;
    }

    /**
     * Applies field metadata declared as ResponseField attributes - on the
     * endpoint's transformer class, or on the controller method itself - to a
     * fractal attributes schema. This is how nullability, enums, and map/union
     * shapes that cannot be inferred from a single example reach the spec.
     *
     * @param  OpenApiSchemaInput  $schema
     *                                      a reference into the schema and loses the key type
     */
    private function applyDeclaredFieldMetadata(array &$schema, OutputEndpointData $endpoint): void
    {
        $transformer = $this->transformerClasses()[$this->anonymousEndpointKey(mb_strtoupper($endpoint->httpMethods[0]), $endpoint->uri)] ?? null;
        if ($transformer !== null) {
            foreach ((new ReflectionClass($transformer))->getAttributes(ResponseField::class) as $attribute) {
                $field = $attribute->newInstance();
                $example = $field->example;
                JsonValueGuard::assertValue($example);
                $enum = $field->enum;
                JsonValueGuard::assertValue($enum);
                $schema = $this->applyFieldMetadata($schema, $field->name, [
                    'description' => $field->description,
                    'type' => $field->type,
                    'required' => $field->required,
                    'example' => $this->arrayValue($example),
                    'enum' => $this->arrayValue($enum),
                    'nullable' => $field->nullable,
                    'schema' => $field->name === 'config.files'
                        ? ['$ref' => '#/components/schemas/AdminEggConfigurationFiles']
                        : $field->schema,
                ]);
            }
        }

        foreach ($endpoint->responseFields as $field) {
            if (! str_starts_with($field->name, 'attributes.')) {
                continue;
            }

            $enumValues = $field->enumValues;
            JsonValueGuard::assertValue($enumValues);
            $schema = $this->applyFieldMetadata($schema, mb_substr($field->name, mb_strlen('attributes.')), [
                'description' => $field->description,
                'type' => $field->type,
                'required' => $field->required,
                'example' => null,
                'enum' => $enumValues,
                'nullable' => $field->nullable,
                'schema' => [],
            ]);
        }
    }

    /**
     * @param  OpenApiSchemaInput  $schema
     * @param  array{description: ?string, type: ?string, required: ?bool, example: JsonValue, enum: JsonValue, nullable: ?bool, schema: OpenApiSchema}  $meta
     * @return OpenApiSchemaInput
     */
    private function applyFieldMetadata(array $schema, string $path, array $meta): array
    {
        $segments = explode('.', $path);
        $leaf = array_pop($segments);

        $parent = &$schema;
        foreach ($segments as $segment) {
            if (! isset($parent['properties'][$segment]) || ! is_array($parent['properties'][$segment])) {
                JsonValueGuard::assertOpenApiSchemaInput($schema);

                return $schema;
            }

            $parent = &$parent['properties'][$segment];
        }

        if ($meta['required'] === false && isset($schema['required']) && is_array($schema['required']) && $segments === []) {
            $schema['required'] = array_values(array_filter(JsonValueGuard::stringList($schema['required']), fn (string $key): bool => $key !== $leaf));
        }

        if (! isset($parent['properties'][$leaf]) || ! is_array($parent['properties'][$leaf])) {
            JsonValueGuard::assertOpenApiSchemaInput($schema);

            return $schema;
        }

        if ($meta['schema'] !== []) {
            $parent['properties'][$leaf] = $meta['schema'];
        }

        $property = &$parent['properties'][$leaf];

        if (($type = $meta['type']) !== null && $type !== '' && $meta['schema'] === []) {
            if (str_ends_with($type, '[]')) {
                $property['type'] = 'array';
                $property['items'] = ['type' => mb_substr($type, 0, -2)];
                unset($property['example']);
            } elseif (str_contains($type, '<')) {
                $property['type'] = $type;
                unset($property['items'], $property['properties'], $property['required']);
            } else {
                $property['type'] = $type;
            }
        }

        if (! empty($meta['description'])) {
            $property['description'] = $meta['description'];
        }

        if ($meta['nullable'] === true) {
            $property['nullable'] = true;
        }

        $enum = $meta['enum'];
        if (is_string($enum) && enum_exists($enum)) {
            $enum = array_map(fn (UnitEnum $case): int|string => $case instanceof BackedEnum ? $case->value : $case->name, $enum::cases());
        }

        if (is_array($enum) && $enum !== []) {
            if (($property['type'] ?? null) === 'array' && isset($property['items']) && is_array($property['items'])) {
                $property['items']['enum'] = $enum;
            } else {
                $property['enum'] = $enum;
            }
        }

        if ($meta['example'] !== null) {
            $property['example'] = $meta['example'];
        }

        JsonValueGuard::assertOpenApiSchemaInput($schema);

        return $schema;
    }

    /**
     * Applies method- and class-level ResponseField declarations to inline
     * response schemas (endpoints documented with literal examples), matching
     * properties by bare field name at any depth.
     *
     * @param  OpenApiOperation  $pathItem
     */
    private function applyResponseFieldsToInlineSchemas(array &$pathItem, OutputEndpointData $endpoint): void
    {
        if ($endpoint->responseFields === [] || ! isset($pathItem['responses']) || ! is_array($pathItem['responses'])) {
            return;
        }

        foreach ($pathItem['responses'] as $status => &$response) {
            if (! str_starts_with($this->arrayKeyString($status), '2') || ! isset($response['content']['application/json']['schema'])) {
                continue;
            }

            $schema = &$response['content']['application/json']['schema'];
            if (is_array($schema) && ! isset($schema['$ref'])) {
                $this->walkPropertiesApplyingResponseFields($schema, $endpoint);
            }
        }

        unset($response);
    }

    /**
     * @param  OpenApiRecursiveNode  $schema
     *
     * @param-out mixed $schema
     */
    private function walkPropertiesApplyingResponseFields(array &$schema, OutputEndpointData $endpoint): void
    {
        // SAFETY: Scribe supplies a heterogeneous recursive OpenAPI tree; every node is narrowed before array access.
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $name => &$property) {
                if (! is_array($property)) {
                    continue;
                }

                $field = $endpoint->responseFields[$name] ?? null;
                if ($field !== null) {
                    if ($field->nullable) {
                        $property['nullable'] = true;
                    }

                    if (! empty($field->description)) {
                        $property['description'] = $field->description;
                    }

                    if ($field->enumValues !== []) {
                        $property['enum'] = $field->enumValues;
                    }

                    if ($field->required === false && isset($schema['required']) && is_array($schema['required'])) {
                        $schema['required'] = array_values(array_filter(JsonValueGuard::stringList($schema['required']), fn (string $key): bool => $key !== $name));
                    }
                }

                JsonValueGuard::assertValue($property);
                $this->walkPropertiesApplyingResponseFields($property, $endpoint);
            }

            unset($property);
        }

        foreach (['items', 'additionalProperties'] as $key) {
            if (isset($schema[$key]) && is_array($schema[$key])) {
                JsonValueGuard::assertValue($schema[$key]);
                $this->walkPropertiesApplyingResponseFields($schema[$key], $endpoint);
            }
        }
    }

    /**
     * Scribe rewrites URL parameter names, so map keys anonymize them to match
     * a Laravel route against its documented endpoint.
     */
    private function anonymousEndpointKey(string $httpMethod, string $uri): string
    {
        return $httpMethod.' '.preg_replace('/\{[^}]+\}/', '{}', mb_trim($uri, '/'));
    }

    /**
     * @return array<string, class-string> map of "METHOD uri" to the transformer class
     *                                     declared by the route action's attribute
     */
    private function transformerClasses(): array
    {
        if ($this->transformerClasses !== null) {
            return $this->transformerClasses;
        }

        $map = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $action = $route->getActionName();
            [$class, $method] = str_contains($action, '@') ? explode('@', $action, 2) : [$action, '__invoke'];
            if ($class === 'Closure' || ! class_exists($class) || ! method_exists($class, $method)) {
                continue;
            }

            $attributes = (new ReflectionMethod($class, $method))->getAttributes(
                ResponseFromTransformer::class,
                ReflectionAttribute::IS_INSTANCEOF,
            );
            if ($attributes === []) {
                continue;
            }

            $transformer = $attributes[0]->newInstance()->name;
            if (! class_exists($transformer)) {
                continue;
            }

            foreach ($route->methods() as $httpMethod) {
                if (! is_string($httpMethod)) {
                    continue;
                }

                if ($httpMethod !== 'HEAD' && $httpMethod !== 'OPTIONS') {
                    $map[$this->anonymousEndpointKey($httpMethod, $route->uri())] = $transformer;
                }
            }
        }

        return $this->transformerClasses = $map;
    }

    /** @return DeepOpenApiSchema */
    private function adminServerVariableAttributesSchema(): array
    {
        $schema = PterodactylDocumentation::schemaForValue([
            'id' => 1,
            'egg_id' => 1,
            'name' => 'Server Jar File',
            'description' => 'The jar file to run.',
            'env_variable' => 'SERVER_JARFILE',
            'default_value' => 'server.jar',
            'server_value' => 'server.jar',
            'user_viewable' => true,
            'user_editable' => true,
            'rules' => 'required|string',
            'required' => true,
            'sort_order' => 1,
            'created_at' => '2026-01-01T00:00:00+00:00',
            'updated_at' => '2026-01-01T00:00:00+00:00',
        ]);

        $properties = $schema['properties'] ?? null;
        JsonValueGuard::assertOpenApiSchemaMap($properties);
        $properties['server_value']['nullable'] = true;
        $properties['sort_order']['nullable'] = true;
        $schema['properties'] = $properties;

        $required = $schema['required'] ?? null;
        JsonValueGuard::assertStringList($required);
        $schema['required'] = array_values(array_diff($required, ['required']));

        return $schema;
    }

    /** @return OpenApiSchema */
    private function clientTwoFactorSetupSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'required' => ['image_url_data', 'secret'],
                    'properties' => [
                        'image_url_data' => [
                            'type' => 'string',
                            'example' => 'otpauth://totp/Pterodactyl:user@example.com?secret=EXAMPLESECRET&issuer=Pterodactyl',
                        ],
                        'secret' => [
                            'type' => 'string',
                            'example' => 'EXAMPLESECRET',
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return DeepOpenApiSchema */
    private function clientSystemPermissionsSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['object', 'attributes'],
            'properties' => [
                'object' => [
                    'type' => 'string',
                    'example' => 'system_permissions',
                ],
                'attributes' => [
                    'type' => 'object',
                    'required' => ['permissions'],
                    'properties' => [
                        'permissions' => [
                            'type' => 'object',
                            'additionalProperties' => [
                                'type' => 'object',
                                'required' => ['description', 'keys'],
                                'properties' => [
                                    'description' => ['type' => 'string'],
                                    'keys' => [
                                        'type' => 'object',
                                        'additionalProperties' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function authLoginResponseSchema(): array
    {
        return [
            'oneOf' => [
                ['$ref' => '#/components/schemas/AuthLoginCompleteResponse'],
                ['$ref' => '#/components/schemas/AuthLoginCheckpointResponse'],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function authLoginCompleteResponseSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'required' => ['complete', 'intended', 'user'],
                    'properties' => [
                        'complete' => [
                            'type' => 'boolean',
                            'enum' => [true],
                            'example' => true,
                        ],
                        'intended' => [
                            'type' => 'string',
                            'example' => '/',
                        ],
                        'user' => ['$ref' => '#/components/schemas/AuthUser'],
                    ],
                ],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function authLoginCheckpointResponseSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'required' => ['complete', 'confirmation_token'],
                    'properties' => [
                        'complete' => [
                            'type' => 'boolean',
                            'enum' => [false],
                            'example' => false,
                        ],
                        'confirmation_token' => [
                            'type' => 'string',
                            'example' => 'b6e22f85d63c4d9db9739f9ab0a27a48f51f7ad38ef8c12fd157c4c6f4b2e51d',
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function authPasswordResetEmailResponseSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['status'],
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'example' => 'We have emailed your password reset link.',
                ],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function authResetPasswordResponseSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['success', 'redirect_to', 'send_to_login'],
            'properties' => [
                'success' => [
                    'type' => 'boolean',
                    'enum' => [true],
                    'example' => true,
                ],
                'redirect_to' => [
                    'type' => 'string',
                    'nullable' => true,
                    'example' => '/',
                ],
                'send_to_login' => [
                    'type' => 'boolean',
                    'example' => false,
                ],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function authUserSchema(): array
    {
        return [
            'type' => 'object',
            'required' => [
                'uuid',
                'username',
                'email',
                'name_first',
                'name_last',
                'language',
                'root_admin',
                'use_totp',
                'gravatar',
                'created_at',
                'updated_at',
                'identifier',
            ],
            'properties' => [
                'uuid' => [
                    'type' => 'string',
                    'format' => 'uuid',
                    'example' => '9a8b7c6d-5e4f-4321-9876-123456789abc',
                ],
                'username' => [
                    'type' => 'string',
                    'example' => 'admin',
                ],
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'example' => 'admin@example.com',
                ],
                'name_first' => [
                    'type' => 'string',
                    'nullable' => true,
                    'example' => 'Admin',
                ],
                'name_last' => [
                    'type' => 'string',
                    'nullable' => true,
                    'example' => 'User',
                ],
                'language' => [
                    'type' => 'string',
                    'example' => 'en',
                ],
                'root_admin' => [
                    'type' => 'boolean',
                    'example' => true,
                ],
                'use_totp' => [
                    'type' => 'boolean',
                    'example' => false,
                ],
                'gravatar' => [
                    'type' => 'boolean',
                    'example' => true,
                ],
                'created_at' => [
                    'type' => 'string',
                    'format' => 'date-time',
                    'example' => '2026-06-29T12:00:00+00:00',
                ],
                'updated_at' => [
                    'type' => 'string',
                    'format' => 'date-time',
                    'example' => '2026-06-29T12:00:00+00:00',
                ],
                'identifier' => [
                    'type' => 'string',
                    'pattern' => '^usr_[a-zA-Z0-9]+$',
                    'example' => 'usr_1a2b3c4d',
                ],
            ],
        ];
    }

    /**
     * @param  OpenApiOperation  $pathItem
     */
    private function replaceSuccessResponseSchema(array &$pathItem, OutputEndpointData $endpoint): void
    {
        $fractal = $this->fractalResponse($endpoint);
        if ($fractal === null) {
            return;
        }

        $components = $this->fractalComponents($endpoint, $fractal['resource']);
        $schema = match (true) {
            $fractal['paginated'] => $components['page'],
            $fractal['collection'] => $components['list'],
            default => $components['item'],
        };

        if (! isset($pathItem['responses']) || ! is_array($pathItem['responses'])) {
            return;
        }

        foreach ($pathItem['responses'] as $status => &$response) {
            if (! str_starts_with($this->arrayKeyString($status), '2') || $this->arrayKeyString($status) === '204') {
                continue;
            }

            if (isset($response['content']['application/json']['schema'])) {
                $response['content']['application/json']['schema'] = ['$ref' => "#/components/schemas/{$schema}"];
            }
        }

        unset($response);
    }

    /**
     * @param  OpenApiOperation  $pathItem
     */
    private function replacePlainSuccessResponseSchema(array &$pathItem): void
    {
        if (in_array($pathItem['operationId'] ?? null, ['adminGetExtensionSettings', 'adminUpdateExtensionSettings', 'adminUploadExtensionSettingFile', 'adminClearExtensionSettingFile'], true)) {
            if (! isset($pathItem['responses']) || ! is_array($pathItem['responses'])) {
                return;
            }

            foreach ($pathItem['responses'] as $status => &$response) {
                if (str_starts_with($this->arrayKeyString($status), '2') && isset($response['content']['application/json']['schema'])) {
                    $response['content']['application/json']['schema'] = $this->adminExtensionSettingsSchema();
                }
            }

            unset($response);

            return;
        }

        $schema = match ($pathItem['operationId'] ?? null) {
            'adminListLanguages' => 'AdminLanguagesResponse',
            'adminGetNodeConfiguration' => 'AdminNodeConfigurationResponse',
            'adminCreateNodeDeployToken' => 'AdminNodeDeployTokenResponse',
            'adminGetNodeSystemInformation' => 'AdminNodeSystemInformationResponse',
            'adminGetNodeUtilization' => 'AdminNodeUtilizationResponse',
            'adminGetSettings' => 'AdminSettingsResponse',
            'adminGetVersionInformation' => 'AdminVersionInformationResponse',
            'authCompleteLoginCheckpoint' => 'AuthLoginCompleteResponse',
            'authLogin' => 'AuthLoginResponse',
            'authRequestPasswordResetEmail' => 'AuthPasswordResetEmailResponse',
            'authResetPassword' => 'AuthResetPasswordResponse',
            'clientListServerPermissions' => 'ClientSystemPermissionsResponse',
            'clientGetTwoFactorSetup' => 'ClientTwoFactorSetupResponse',
            default => null,
        };

        if ($schema === null || ! isset($pathItem['responses']) || ! is_array($pathItem['responses'])) {
            return;
        }

        foreach ($pathItem['responses'] as $status => &$response) {
            if (! str_starts_with($this->arrayKeyString($status), '2') || ! isset($response['content']['application/json']['schema'])) {
                continue;
            }

            $response['content']['application/json']['schema'] = ['$ref' => "#/components/schemas/{$schema}"];
        }

        unset($response);
    }

    /**
     * @param  OpenApiOperation  $pathItem
     */
    private function stripEmptySuccessResponseContent(array &$pathItem): void
    {
        if (! isset($pathItem['responses']) || ! is_array($pathItem['responses'])) {
            return;
        }

        foreach ($pathItem['responses'] as $status => &$response) {
            if (! str_starts_with($this->arrayKeyString($status), '2')) {
                continue;
            }

            $schema = $response['content']['application/json']['schema'] ?? null;
            if (! is_array($schema)) {
                continue;
            }

            if (($schema['type'] ?? null) === 'object' && ($schema['nullable'] ?? false) === true && ! isset($schema['properties'])) {
                unset($response['content']);
            }
        }

        unset($response);
    }

    /**
     * @param  OpenApiOperation  $pathItem
     * @return OpenApiOperation
     */
    private function normalizeBrowserSessionOperation(array $pathItem, OutputEndpointData $endpoint): array
    {
        if (! str_starts_with($endpoint->uri, 'auth/') && $endpoint->uri !== 'sanctum/csrf-cookie') {
            return $pathItem;
        }

        $pathItem['security'] = [];

        if (($pathItem['operationId'] ?? null) === 'authLogout') {
            $pathItem['security'] = [['panelSession' => []]];
        }

        if (($pathItem['operationId'] ?? null) !== 'authGetCsrfCookie') {
            return $pathItem;
        }

        $pathItem['summary'] = 'Get CSRF cookie';
        $pathItem['description'] = 'Initializes the Laravel Sanctum CSRF cookie required before browser-session authentication requests.';
        $pathItem['responses'] = [
            204 => [
                'description' => 'The CSRF cookie was queued on the response.',
            ],
        ];

        return $pathItem;
    }

    /**
     * @param  OpenApiOperation  $pathItem
     */
    private function normalizeRawResponseMediaTypes(array &$pathItem, OutputEndpointData $endpoint): void
    {
        if (($pathItem['operationId'] ?? null) !== 'adminExportEgg'
            && preg_match('#^api/admin/eggs/\{[^}]+\}/export$#', $endpoint->uri) !== 1
        ) {
            return;
        }

        if (! isset($pathItem['responses']) || ! is_array($pathItem['responses'])) {
            return;
        }

        foreach ($pathItem['responses'] as $status => &$response) {
            if (! str_starts_with($this->arrayKeyString($status), '2') || ! isset($response['content']['application/json'])) {
                continue;
            }

            $media = $response['content']['application/json'];
            $media['schema'] = [
                'type' => 'string',
                'description' => 'Raw egg export JSON document returned as a text download.',
                'example' => '{"_comment":"DO NOT EDIT: FILE GENERATED AUTOMATICALLY BY PANEL","meta":{"version":"PTDL_v2"}}',
            ];

            $response['content'] = [
                'text/plain' => $media,
            ];
        }

        unset($response);
    }

    /**
     * @param  OpenApiOperation  $pathItem
     */
    private function replaceErrorResponseSchemas(array &$pathItem): void
    {
        if (! isset($pathItem['responses']) || ! is_array($pathItem['responses'])) {
            return;
        }

        foreach ($pathItem['responses'] as $status => &$response) {
            $schema = match ($this->arrayKeyString($status)) {
                '400', '401', '403', '404', '409', '429', '500', '502', '504' => 'ErrorEnvelope',
                '422' => 'ValidationErrorEnvelope',
                default => null,
            };

            if ($schema !== null && isset($response['content']['application/json']['schema'])) {
                $response['content']['application/json']['schema'] = ['$ref' => "#/components/schemas/{$schema}"];
            }
        }

        unset($response);
    }

    /**
     * @param  OpenApiOperation  $pathItem
     * @return OpenApiOperation
     */
    private function normalizeSchemas(array $pathItem): array
    {
        // SAFETY: root path entries include non-operation OpenAPI values; every operation field is narrowed before use.
        $parameters = $pathItem['parameters'] ?? null;
        if (is_array($parameters)) {
            foreach ($parameters as &$parameter) {
                if (isset($parameter['schema']) && is_array($parameter['schema'])) {
                    JsonValueGuard::assertValue($parameter['schema']);
                    $this->normalizeSchema($parameter['schema']);
                    JsonValueGuard::assertPayload9($parameter['schema']);
                }
            }

            unset($parameter);
            $pathItem['parameters'] = $parameters;
        }

        if (isset($pathItem['requestBody'])) {
            $requestBody = $this->openApiContainerValue($pathItem['requestBody']);
            $content = is_array($requestBody) ? ($requestBody['content'] ?? null) : null;
            if (is_array($content)) {
                JsonValueGuard::assertOpenApiContent($content);
                $requestBody['content'] = $this->normalizeContentSchemas($content);
            }

            $pathItem['requestBody'] = $requestBody;
        }

        $responses = $pathItem['responses'] ?? null;
        if (is_array($responses)) {
            foreach ($responses as &$response) {
                $response = $this->openApiContainerValue($response);
                if (! is_array($response)) {
                    continue;
                }

                $content = $response['content'] ?? null;
                if (is_array($content)) {
                    JsonValueGuard::assertOpenApiContent($content);
                    $response['content'] = $this->normalizeContentSchemas($content);
                }
            }

            unset($response);
            $pathItem['responses'] = $responses;
        }

        JsonValueGuard::assertOpenApiOperation($pathItem);

        return $pathItem;
    }

    /**
     * @param  OpenApiRoot  $root
     */
    private function normalizeRootSchemas(array &$root): void
    {
        foreach ($root['paths'] ?? [] as &$pathItem) {
            foreach ($pathItem as &$operation) {
                if (is_array($operation)) {
                    JsonValueGuard::assertOpenApiOperation($operation);
                    $operation = $this->normalizeSchemas($operation);
                }
            }

            unset($operation);
        }

        unset($pathItem);

        foreach ($root['components']['schemas'] as &$schema) {
            $this->normalizeSchema($schema);
        }

        unset($schema);
    }

    /**
     * @param  OpenApiRoot  $root
     */
    private function pruneUnreferencedSchemas(array &$root): void
    {
        $schemas = &$root['components']['schemas'];
        $reachable = $this->operationSchemaRefs;
        $pending = array_keys($reachable);

        while ($pending !== []) {
            $name = array_pop($pending);
            if (! isset($schemas[$name])) {
                continue;
            }

            $refs = [];
            $this->collectComponentRefs($schemas[$name], $refs);
            foreach ($refs as $ref => $_) {
                if (! isset($reachable[$ref])) {
                    $reachable[$ref] = true;
                    $pending[] = $ref;
                }
            }
        }

        foreach (array_keys($schemas) as $name) {
            if (! isset($reachable[$name])) {
                unset($schemas[$name]);
            }
        }
    }

    /**
     * @param  JsonInputValue  $node
     * @param  array<string, true>  $refs
     */
    private function collectComponentRefs(mixed $node, array &$refs): void
    {
        if (! is_array($node)) {
            return;
        }

        $ref = $node['$ref'] ?? null;
        if (is_string($ref) && str_starts_with($ref, '#/components/schemas/')) {
            $refs[mb_substr($ref, mb_strlen('#/components/schemas/'))] = true;
        }

        foreach ($node as $value) {
            JsonValueGuard::assertValue($value);
            $this->collectComponentRefs($value, $refs);
        }
    }

    /**
     * @param  OpenApiOperation  $operation
     */
    private function extractResponseBodySchemas(array &$operation): void
    {
        $operationId = $operation['operationId'] ?? '';
        $operationId = is_string($operationId) ? $operationId : '';
        if ($operationId === '' || ! isset($operation['responses']) || ! is_array($operation['responses'])) {
            return;
        }

        $inlineSchemas = [];
        foreach ($operation['responses'] as $status => $response) {
            $content = $response['content'] ?? null;
            if (! is_array($content)) {
                continue;
            }

            foreach ($content as $contentType => $media) {
                if (isset($media['schema']) && is_array($media['schema']) && ! isset($media['schema']['$ref'])) {
                    $inlineSchemas[] = [$this->arrayKeyString($status), $this->arrayKeyString($contentType)];
                }
            }
        }

        $hasMultipleSchemas = count($inlineSchemas) > 1;

        foreach ($operation['responses'] as $status => &$response) {
            if (! isset($response['content']) || ! is_array($response['content'])) {
                continue;
            }

            foreach ($response['content'] as $contentType => &$media) {
                if (! isset($media['schema']) || ! is_array($media['schema']) || isset($media['schema']['$ref'])) {
                    continue;
                }

                $this->normalizeNestedArrayItemSchemas($media['schema']);
                JsonValueGuard::assertPayload9($media['schema']);
                $this->normalizeSchema($media['schema']);
                JsonValueGuard::assertPayload9($media['schema']);

                $component = $this->responseBodyComponentName($operationId, $this->arrayKeyString($status), $this->arrayKeyString($contentType), $hasMultipleSchemas);
                $component = $this->availableComponentName($this->responseBodySchemas, $component, $media['schema']);
                $this->responseBodySchemas[$component] = $media['schema'];
                $media['schema'] = ['$ref' => "#/components/schemas/{$component}"];
            }

            unset($media);
        }

        unset($response);
    }

    /**
     * @param  OpenApiOperation  $operation
     */
    private function extractOperationParameterSchemas(array &$operation): void
    {
        $operationId = $operation['operationId'] ?? '';
        $operationId = is_string($operationId) ? $operationId : '';
        if ($operationId === '' || ! isset($operation['parameters']) || ! is_array($operation['parameters'])) {
            return;
        }

        foreach ($operation['parameters'] as &$parameter) {
            $this->extractParameterSchema($parameter, $this->operationParameterSchemaComponentName($operationId, $parameter));
        }

        unset($parameter);
    }

    /**
     * @param  ScribeParameter  $parameter
     */
    private function extractParameterSchema(array &$parameter, string $component): void
    {
        if (! isset($parameter['schema']) || ! is_array($parameter['schema']) || isset($parameter['schema']['$ref'])) {
            return;
        }

        JsonValueGuard::assertPayload9($parameter['schema']);
        $component = $this->availableComponentName($this->parameterSchemas, $component, $parameter['schema']);
        $this->parameterSchemas[$component] = $parameter['schema'];
        $parameter['schema'] = ['$ref' => "#/components/schemas/{$component}"];
    }

    /**
     * @param  OpenApiOperation  $operation
     */
    private function extractRequestBodySchemas(array &$operation): void
    {
        $operationId = $operation['operationId'] ?? '';
        $operationId = is_string($operationId) ? $operationId : '';
        if ($operationId === '' || ! isset($operation['requestBody']['content']) || ! is_array($operation['requestBody']['content'])) {
            return;
        }

        $mediaWithSchemas = array_filter(
            $operation['requestBody']['content'],
            fn (array $media): bool => isset($media['schema']) && is_array($media['schema']) && ! isset($media['schema']['$ref'])
        );
        $hasMultipleSchemas = count($mediaWithSchemas) > 1;

        foreach ($operation['requestBody']['content'] as $contentType => &$media) {
            if (! isset($media['schema']) || ! is_array($media['schema']) || isset($media['schema']['$ref'])) {
                continue;
            }

            $component = $this->requestBodyComponentName($operationId, $this->arrayKeyString($contentType), $hasMultipleSchemas);
            $component = $this->availableComponentName($this->requestBodySchemas, $component, $media['schema']);
            $this->requestBodySchemas[$component] = $media['schema'];
            $media['schema'] = ['$ref' => "#/components/schemas/{$component}"];
        }

        unset($media);
    }

    /**
     * @param  OpenApiSchemaMap  $schemas
     * @param  DeepOpenApiSchema  $schema
     */
    private function availableComponentName(array $schemas, string $name, array $schema): string
    {
        if (! isset($schemas[$name]) || $schemas[$name] === $schema) {
            return $name;
        }

        $index = 2;
        while (isset($schemas["{$name}{$index}"]) && $schemas["{$name}{$index}"] !== $schema) {
            $index++;
        }

        return "{$name}{$index}";
    }

    private function requestBodyComponentName(string $operationId, string $contentType, bool $includeContentType): string
    {
        $name = Str::of($operationId)->studly()->append('Request');
        if (! $includeContentType) {
            return $name->toString();
        }

        return $name
            ->append(Str::of($contentType)->replace(['/', '+', '.', '-'], ' ')->studly()->toString())
            ->toString();
    }

    private function responseBodyComponentName(string $operationId, string $status, string $contentType, bool $includeStatusAndContentType): string
    {
        $name = Str::of($operationId)->studly();
        if ($includeStatusAndContentType) {
            $name = $name
                ->append($status)
                ->append(Str::of($contentType)->replace(['/', '+', '.', '-'], ' ')->studly()->toString());
        }

        return $name->append('ResponseBody')->toString();
    }

    /**
     * @param  ScribeParameter  $parameter
     */
    private function operationParameterSchemaComponentName(string $operationId, array $parameter): string
    {
        $locationValue = $parameter['in'] ?? 'parameter';
        $location = Str::of(is_string($locationValue) ? $locationValue : 'parameter')->studly();
        $name = $this->parameterNameSegment($parameter);

        return Str::of($operationId)
            ->studly()
            ->append($name)
            ->append($location->toString())
            ->append('Parameter')
            ->toString();
    }

    /**
     * @param  ScribeParameter  $parameter
     */
    private function pathParameterSchemaComponentName(OutputEndpointData $endpoint, array $parameter): string
    {
        return Str::of($this->baseOperationId($endpoint))
            ->studly()
            ->append($this->parameterNameSegment($parameter))
            ->append('PathParameter')
            ->toString();
    }

    /**
     * @param  ScribeParameter  $parameter
     */
    private function parameterNameSegment(array $parameter): string
    {
        $name = $parameter['name'] ?? 'value';

        return Str::of(is_string($name) ? $name : 'value')
            ->replace('*', ' wildcard ')
            ->replace(['[', ']', '/', '+', '.', '-', '_'], ' ')
            ->studly()
            ->toString();
    }

    /**
     * @param  OpenApiOperation  $pathItem
     */
    private function enforceKnownBodyParameterSchemas(array &$pathItem, OutputEndpointData $endpoint): void
    {
        if (! isset($pathItem['requestBody']['content']) || ! is_array($pathItem['requestBody']['content'])) {
            return;
        }

        $contentType = array_key_first($pathItem['requestBody']['content']);
        if (! is_string($contentType)) {
            return;
        }

        $media = &$pathItem['requestBody']['content'][$contentType];
        $schema = $media['schema'] ?? null;
        if (! is_array($schema)) {
            return;
        }

        $properties = $schema['properties'];
        if (! is_array($properties)) {
            return;
        }

        $properties = $this->normalizeScribeValue($properties);
        if (! is_array($properties)) {
            return;
        }

        JsonValueGuard::assertOpenApiSchemaMap($properties);
        $schema['properties'] = &$properties;
        $media['schema'] = &$schema;

        if (isset($endpoint->bodyParameters['environment'])) {
            $properties['environment'] = [
                'type' => 'object',
                'description' => PterodactylDocumentation::fieldDescription('environment'),
                'additionalProperties' => ['type' => 'string'],
                'example' => ['SERVER_JARFILE' => 'server.jar'],
            ];
        }

        foreach (['features', 'file_denylist'] as $name) {
            if (isset($endpoint->bodyParameters[$name])) {
                $properties[$name] = [
                    'type' => 'array',
                    'description' => PterodactylDocumentation::fieldDescription($name),
                    'items' => ['type' => 'string'],
                    'example' => PterodactylDocumentation::fieldExample($name, 'string[]'),
                ];
            }
        }

        if (isset($endpoint->bodyParameters['permissions'])) {
            $properties['permissions'] = [
                ...$this->permissionsSchema(),
                'description' => PterodactylDocumentation::fieldDescription('permissions'),
            ];
        }

        if (isset($endpoint->bodyParameters['preferences.language'])) {
            $preferenceProperties = $properties['preferences']['properties'] ?? null;
            if (is_array($preferenceProperties)) {
                JsonValueGuard::assertOpenApiSchemaMap($preferenceProperties);
                $preferenceProperties['language'] = [
                    'type' => 'string',
                    'description' => PterodactylDocumentation::fieldDescription('preferences.language'),
                    'example' => 'en',
                ];
                $properties['preferences']['properties'] = $preferenceProperties;
            }
        }

        if (isset($properties['extensions'])) {
            $properties['extensions'] = ['$ref' => '#/components/schemas/ExtensionFields'];
        }

        JsonValueGuard::assertOpenApiSchemaMap($properties);
        match ($pathItem['operationId'] ?? null) {
            'applicationCreateServer' => $this->enforceApplicationCreateServerBodySchema($properties),
            'adminUpdateGeneralSettings' => $this->enforceAdminGeneralSettingsBodySchema($properties),
            'adminUpdateMailSettings' => $this->enforceAdminMailSettingsBodySchema($properties),
            'adminUpdateMinecraftVersionChangerSettings' => $this->enforceMinecraftVersionChangerSettingsBodySchema($properties),
            'adminUpdateExtensionSettings' => $this->enforceAdminExtensionSettingsBodySchema($properties),
            default => null,
        };
    }

    /**
     * @param  OpenApiSchemaMap  $properties
     */
    private function enforceApplicationCreateServerBodySchema(array &$properties): void
    {
        if (! isset($properties['deploy']['properties'])) {
            return;
        }

        $deploy = &$properties['deploy']['properties'];
        JsonValueGuard::assertOpenApiSchemaMap($deploy);
        $deploy['dedicated_ip'] = [
            'type' => 'boolean',
            'description' => PterodactylDocumentation::fieldDescription('deploy.dedicated_ip'),
            'example' => false,
        ];
        $deploy['tags'] = [
            'type' => 'array',
            'description' => PterodactylDocumentation::fieldDescription('deploy.tags'),
            'items' => [
                'oneOf' => [
                    ['type' => 'integer'],
                    ['type' => 'string'],
                ],
            ],
            'example' => ['minecraft', 1],
        ];
    }

    /**
     * @param  OpenApiSchemaMap  $properties
     */
    private function enforceAdminExtensionSettingsBodySchema(array &$properties): void
    {
        $properties['settings'] = [
            'type' => 'object',
            'description' => 'Field values keyed by input name, as described by the settings schema.',
            'additionalProperties' => [
                ...$this->extensionSettingValueSchema(),
                'nullable' => true,
            ],
            'example' => [
                'curseforge_api_key' => 'cf-key',
                'enabled' => true,
                'result_limit' => 25,
            ],
        ];
    }

    /**
     * @param  OpenApiSchemaMap  $properties
     */
    private function enforceMinecraftVersionChangerSettingsBodySchema(array &$properties): void
    {
        if (isset($properties['typeCategories'])) {
            $properties['typeCategories'] = [
                'type' => 'object',
                'description' => 'Map of display category names to MCJars server type identifiers.',
                'additionalProperties' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'example' => ['Recommended' => ['VANILLA', 'PAPER']],
            ];
        }
    }

    /**
     * @param  OpenApiSchemaMap  $properties
     */
    private function enforceAdminGeneralSettingsBodySchema(array &$properties): void
    {
        if (isset($properties['default_locale'])) {
            $properties['default_locale'] = [
                'type' => 'string',
                'description' => PterodactylDocumentation::fieldDescription('default_locale'),
                'example' => 'en',
            ];
        }
    }

    /**
     * @param  OpenApiSchemaMap  $properties
     */
    private function enforceAdminMailSettingsBodySchema(array &$properties): void
    {
        if (! isset($properties['smtp']['properties']['encryption'])) {
            return;
        }

        $properties['smtp']['properties']['encryption'] = [
            'type' => 'string',
            'nullable' => true,
            'enum' => ['tls', 'ssl', null],
            'description' => PterodactylDocumentation::fieldDescription('smtp.encryption'),
            'example' => 'tls',
        ];
    }

    /**
     * @param  OpenApiOperation  $pathItem
     * @return OpenApiOperation
     */
    private function normalizeOperationRequestBody(array $pathItem, OutputEndpointData $endpoint): array
    {
        // SAFETY: Scribe supplies the operation tree; the value is narrowed before request-body mutation.
        if (mb_strtoupper($endpoint->httpMethods[0]) === 'GET') {
            $pathItem = array_diff_key($pathItem, ['requestBody' => true]);
            JsonValueGuard::assertOpenApiOperation($pathItem);

            return $pathItem;
        }

        if (($pathItem['operationId'] ?? null) !== 'clientWriteFileContents') {
            return $pathItem;
        }

        $pathItem['requestBody'] = [
            'required' => true,
            'content' => [
                'text/plain' => [
                    'schema' => [
                        'type' => 'string',
                        'description' => 'Raw file contents to write.',
                        'example' => "motd=A Minecraft Server\nserver-port=25565",
                    ],
                ],
            ],
        ];

        return $pathItem;
    }

    /**
     * @param  OpenApiContent  $content
     * @return OpenApiContent
     */
    private function normalizeContentSchemas(array $content): array
    {
        foreach ($content as &$media) {
            if (isset($media['schema']) && is_array($media['schema'])) {
                $media['schema'] = $this->normalizeScribeValue($media['schema']);
                if (! is_array($media['schema'])) {
                    continue;
                }

                $this->normalizeSchema($media['schema']);
                JsonValueGuard::assertPayload9($media['schema']);
            }
        }

        unset($media);

        JsonValueGuard::assertOpenApiContent($content);

        return $content;
    }

    /**
     * @param  OpenApiRecursiveNode  $schema
     *
     * @param-out mixed $schema
     */
    private function normalizeSchema(array &$schema): void
    {
        // SAFETY: recursive OpenAPI nodes have varying bounded depths; every value is narrowed before array access.
        if (($schema['type'] ?? null) === 'object<string,string>') {
            $schema['type'] = 'object';
            $schema['additionalProperties'] = ['type' => 'string'];
        }

        if (($schema['type'] ?? null) === 'object<string,string[]>') {
            $schema['type'] = 'object';
            $schema['additionalProperties'] = [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ];
        }

        if (
            ($schema['type'] ?? null) === 'array'
            && isset($schema['items'])
            && is_array($schema['items'])
            && ($schema['items']['type'] ?? null) === 'array'
            && ! isset($schema['items']['items'])
            && isset($schema['example'][0])
        ) {
            $schema['items'] = PterodactylDocumentation::schemaForValue($schema['example'][0]);
        }

        foreach (['properties', 'items', 'additionalProperties', 'oneOf', 'anyOf', 'allOf'] as $key) {
            if (! isset($schema[$key]) || ! is_array($schema[$key])) {
                continue;
            }

            $child = &$schema[$key];
            if (array_is_list($child)) {
                foreach ($child as &$item) {
                    if (is_array($item)) {
                        JsonValueGuard::assertValue($item);
                        $this->normalizeSchema($item);
                    }
                }

                unset($item);

                continue;
            }

            if (isset($child['type']) || isset($child['$ref'])) {
                JsonValueGuard::assertValue($schema[$key]);
                $this->normalizeSchema($schema[$key]);

                continue;
            }

            foreach ($child as $name => &$item) {
                if (is_array($item)) {
                    if ($name === 'installation' && ($item['type'] ?? null) === 'array') {
                        $item['items'] = [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ];
                    }

                    JsonValueGuard::assertValue($item);
                    $this->normalizeSchema($item);
                }
            }

            unset($item);
            unset($child);
        }
    }

    /**
     * @param  OpenApiRecursiveNode  $schema
     *
     * @param-out mixed $schema
     */
    private function normalizeNestedArrayItemSchemas(array &$schema): void
    {
        // SAFETY: recursive OpenAPI nodes have varying bounded depths; every value is narrowed before array access.
        if (
            ($schema['type'] ?? null) === 'array'
            && isset($schema['items'])
            && is_array($schema['items'])
            && ($schema['items']['type'] ?? null) === 'array'
            && ! isset($schema['items']['items'])
            && isset($schema['example'][0])
            && is_array($schema['example'][0])
        ) {
            $schema['items'] = PterodactylDocumentation::schemaForValue($schema['example'][0]);
        }

        foreach ($schema as &$value) {
            if (is_array($value)) {
                JsonValueGuard::assertValue($value);
                $this->normalizeNestedArrayItemSchemas($value);
            }
        }

        unset($value);
    }

    /**
     * @param  JsonInputValue|stdClass  $value
     * @return JsonValue
     */
    private function arrayValue(bool|float|int|string|array|stdClass|null $value): bool|float|int|string|array|null
    {
        // SAFETY: Scribe exposes decoded values as mixed; this boundary rejects non-JSON values before returning them.
        if ($value instanceof stdClass) {
            return JsonValueGuard::decode(json_encode($value, JSON_THROW_ON_ERROR));
        }

        JsonValueGuard::assertValue($value);

        return $value;
    }

    /**
     * @param  JsonValue|OpenApiRequestBody|OpenApiResponse|stdClass  $value
     * @return JsonValue|OpenApiRequestBody|OpenApiResponse
     */
    private function openApiContainerValue(bool|float|int|string|array|stdClass|null $value): bool|float|int|string|array|null
    {
        if ($value instanceof stdClass) {
            return JsonValueGuard::decode(json_encode($value, JSON_THROW_ON_ERROR));
        }

        return $value;
    }

    /**
     * @param  JsonInputValue|stdClass  $value
     * @return JsonValue
     */
    private function normalizeScribeValue(mixed $value): bool|float|int|string|array|null
    {
        return JsonValueGuard::decode(json_encode($value, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{attributes: string, item: string, list: string, page: string}
     */
    private function fractalComponents(OutputEndpointData $endpoint, string $resource): array
    {
        return $this->fractalComponentNames($this->surface($endpoint), $resource);
    }

    /**
     * @return array{attributes: string, item: string, list: string, page: string}
     */
    private function fractalComponentNames(string $surface, string $resource): array
    {
        $name = Str::of(ucfirst($surface).' '.$resource)
            ->studly()
            ->toString();

        return [
            'attributes' => "{$name}Attributes",
            'item' => "{$name}Resource",
            'list' => "{$name}ListResponse",
            'page' => "{$name}PaginatedResponse",
        ];
    }

    /**
     * @param  OpenApiSchemaInput  $schema
     * @param  ApiPayload9  $relationships
     * @param  array<string, true>  $knownComponents
     */
    private function addRelationshipsToAttributesSchema(array &$schema, OutputEndpointData $endpoint, array $relationships, array $knownComponents): void
    {
        // SAFETY: generated schemas vary in depth; the mutable object-schema fields are validated before use.
        $properties = $schema['properties'] ?? null;
        JsonValueGuard::assertOpenApiSchemaMap($properties);
        $relationshipSchema = $this->relationshipsSchema($endpoint, $relationships, $knownComponents);
        $existing = $properties['relationships'] ?? null;

        if (is_array($existing)) {
            $existingProperties = $existing['properties'] ?? [];
            JsonValueGuard::assertOpenApiSchemaMap($existingProperties);
            $relationshipSchema['properties'] = [
                ...$existingProperties,
                ...$relationshipSchema['properties'],
            ];
        }

        $properties['relationships'] = $relationshipSchema;
        $updatedSchema = $schema;
        $updatedSchema['properties'] = $properties;

        if (isset($updatedSchema['required'])) {
            JsonValueGuard::assertStringList($updatedSchema['required']);
            $updatedSchema['required'] = array_values(array_filter(
                $updatedSchema['required'],
                fn (string $key): bool => $key !== 'relationships'
            ));
        }

        JsonValueGuard::assertOpenApiSchemaInput($updatedSchema);
        $schema = $updatedSchema;
    }

    /**
     * @param  ApiPayload9  $relationships
     * @param  array<string, true>  $knownComponents
     * @return OpenApiObjectSchema
     */
    private function relationshipsSchema(OutputEndpointData $endpoint, array $relationships, array $knownComponents): array
    {
        $properties = [];

        foreach ($relationships as $name => $relationship) {
            $properties[$name] = $this->relationshipValueSchema($endpoint, $this->arrayKeyString($name), $relationship, $knownComponents);
        }

        return [
            'type' => 'object',
            'properties' => $properties,
        ];
    }

    /**
     * @param  JsonInputValue  $relationship
     * @param  array<string, true>  $knownComponents
     * @return DeepOpenApiSchema
     */
    private function relationshipValueSchema(OutputEndpointData $endpoint, string $name, mixed $relationship, array $knownComponents): array
    {
        if (! is_array($relationship)) {
            return PterodactylDocumentation::schemaForValue($relationship);
        }

        if (($relationship['object'] ?? null) === 'list') {
            $item = $relationship['data'][0] ?? null;
            if (is_array($item) && is_string($item['object'] ?? null)) {
                return $this->relationshipRefSchema($endpoint, $knownComponents, 'list', $name, $item['object'])
                    ?? PterodactylDocumentation::schemaForValue($relationship);
            }

            return $this->relationshipRefSchema($endpoint, $knownComponents, 'list', $name)
                ?? PterodactylDocumentation::schemaForValue($relationship);
        }

        if (is_string($relationship['object'] ?? null) && is_array($relationship['attributes'] ?? null)) {
            return $this->relationshipRefSchema($endpoint, $knownComponents, 'item', $name, $relationship['object'])
                ?? PterodactylDocumentation::schemaForValue($relationship);
        }

        return PterodactylDocumentation::schemaForValue($relationship);
    }

    /**
     * @param  array<string, true>  $knownComponents
     * @param  'item'|'list'  $kind
     * @return DeepOpenApiSchema|null
     */
    private function relationshipRefSchema(OutputEndpointData $endpoint, array $knownComponents, string $kind, string $relationship, ?string $resource = null): ?array
    {
        $componentKey = $kind === 'list' ? 'list' : 'item';

        foreach ($this->relationshipResourceCandidates($relationship, $resource) as $candidate) {
            $components = $this->fractalComponentNames($this->surface($endpoint), $candidate);
            if (isset($knownComponents[$components[$componentKey]])) {
                // Any include resolves to a null resource when the viewer lacks
                // permission for it, so relationships are documented as a union.
                return [
                    'oneOf' => [
                        ['$ref' => "#/components/schemas/{$components[$componentKey]}"],
                        ['$ref' => '#/components/schemas/NullResource'],
                    ],
                ];
            }
        }

        return null;
    }

    /** @return OpenApiSchema */
    private function nullResourceSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['object', 'attributes'],
            'properties' => [
                'object' => ['type' => 'string', 'enum' => ['null_resource'], 'example' => 'null_resource'],
                'attributes' => ['nullable' => true, 'enum' => [null]],
            ],
        ];
    }

    /**
     * @return string[]
     */
    private function relationshipResourceCandidates(string $relationship, ?string $resource): array
    {
        $candidates = [];

        if ($resource !== null) {
            $candidates[] = $resource;
        }

        $candidates[] = Str::singular($relationship);

        $mapped = match ($relationship) {
            'allocations' => 'allocation',
            'tasks' => 'schedule_task',
            'variables' => 'egg_variable',
            default => null,
        };

        if ($mapped !== null) {
            $candidates[] = $mapped;
        }

        return array_values(array_unique($candidates));
    }

    /** @return OpenApiSchema */
    private function fractalItemSchema(string $resource, string $attributesSchema): array
    {
        return [
            'type' => 'object',
            'required' => ['object', 'attributes'],
            'properties' => [
                'object' => [
                    'type' => 'string',
                    'example' => $resource,
                ],
                'attributes' => ['$ref' => "#/components/schemas/{$attributesSchema}"],
            ],
        ];
    }

    /**
     * @param  OpenApiSchemaInput  $schema
     * @param  ApiPayload9  $meta
     */
    private function addMetaToItemSchema(array &$schema, array $meta): void
    {
        $this->addMetaProperty($schema, $meta);
    }

    /**
     * @param  OpenApiSchemaInput  $schema
     * @param  ApiPayload9  $meta
     */
    private function addMetaToListSchema(array &$schema, array $meta): void
    {
        $this->addMetaProperty($schema, $meta);
    }

    /**
     * @param  OpenApiSchemaInput  $schema
     * @param  ApiPayload9  $meta
     */
    private function addMetaToPaginatedSchema(array &$schema, array $meta): void
    {
        // SAFETY: generated paginated schemas vary in depth; each mutable schema map and list is validated before use.
        $properties = $schema['properties'] ?? null;
        JsonValueGuard::assertOpenApiSchemaMap($properties);
        $paginationMeta = $properties['meta'] ?? [];
        $metaSchema = $this->metaSchema($meta);
        $required = $paginationMeta['required'] ?? [];
        JsonValueGuard::assertStringList($required);
        $metaRequired = $metaSchema['required'] ?? [];
        $paginationMeta['required'] = array_values(array_unique([
            ...$required,
            ...$metaRequired,
        ]));
        $paginationProperties = $paginationMeta['properties'] ?? [];
        JsonValueGuard::assertOpenApiSchemaMap($paginationProperties);
        $paginationMeta['properties'] = [
            ...$paginationProperties,
            ...$metaSchema['properties'],
        ];
        $properties['meta'] = $paginationMeta;
        $updatedSchema = $schema;
        $updatedSchema['properties'] = $properties;
        JsonValueGuard::assertOpenApiSchemaInput($updatedSchema);
        $schema = $updatedSchema;
    }

    /**
     * @param  OpenApiSchemaInput  $schema
     * @param  ApiPayload9  $meta
     */
    private function addMetaProperty(array &$schema, array $meta): void
    {
        // SAFETY: generated schemas vary in depth; the properties map is validated before mutation.
        $properties = $schema['properties'] ?? null;
        JsonValueGuard::assertOpenApiSchemaMap($properties);
        $properties['meta'] = $this->metaSchema($meta);
        $updatedSchema = $schema;
        $updatedSchema['properties'] = $properties;
        JsonValueGuard::assertOpenApiSchemaInput($updatedSchema);
        $schema = $updatedSchema;
    }

    /**
     * @param  array<string, JsonInputValue>  $meta
     * @return OpenApiObjectSchema
     */
    private function metaSchema(array $meta): array
    {
        $properties = [];
        foreach ($meta as $key => $value) {
            $properties[$key] = PterodactylDocumentation::schemaForValue($value);
        }

        $schema = [
            'type' => 'object',
            'required' => array_keys($properties),
            'properties' => $properties,
        ];

        if (isset($schema['properties']['docker_images'])) {
            $schema['properties']['docker_images'] = [
                'type' => 'object',
                'additionalProperties' => ['type' => 'string'],
                'example' => ['Java 23' => 'ghcr.io/pterodactyl/yolks:java_23'],
            ];
        }

        return $schema;
    }

    /** @return FractalListOpenApiSchema */
    private function fractalListSchema(string $itemSchema): array
    {
        return [
            'type' => 'object',
            'required' => ['object', 'data'],
            'properties' => [
                'object' => [
                    'type' => 'string',
                    'example' => 'list',
                ],
                'data' => [
                    'type' => 'array',
                    'items' => ['$ref' => "#/components/schemas/{$itemSchema}"],
                ],
            ],
        ];
    }

    /** @return FractalPaginatedOpenApiSchema */
    private function fractalPaginatedSchema(string $itemSchema): array
    {
        $schema = $this->fractalListSchema($itemSchema);
        $schema['required'][] = 'meta';
        $schema['properties']['meta'] = [
            'type' => 'object',
            'required' => ['pagination'],
            'properties' => [
                'pagination' => ['$ref' => '#/components/schemas/PaginationMeta'],
            ],
        ];

        return $schema;
    }

    /** @return OpenApiSchema */
    private function paginationMetaSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['total', 'count', 'per_page', 'current_page', 'total_pages'],
            'properties' => [
                'total' => ['type' => 'integer', 'example' => 1],
                'count' => ['type' => 'integer', 'example' => 1],
                'per_page' => ['type' => 'integer', 'example' => 50],
                'current_page' => ['type' => 'integer', 'example' => 1],
                'total_pages' => ['type' => 'integer', 'example' => 1],
                'links' => [
                    'type' => 'object',
                    'additionalProperties' => ['type' => 'string'],
                ],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function adminLanguagesSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'Available panel locales keyed by locale code.',
            'additionalProperties' => ['type' => 'string'],
            'example' => ['en' => 'English'],
        ];
    }

    /** @return OpenApiSchema */
    private function permissionsSchema(): array
    {
        $permissions = Permission::permissions()
            ->map(fn (array $value, string $prefix): array => array_map(
                fn (int|string $permission): string => "{$prefix}.{$permission}",
                array_keys($value['keys'])
            ))
            ->flatten()
            ->values()
            ->all();
        JsonValueGuard::assertStringList($permissions);

        return [
            'type' => 'array',
            'items' => [
                'anyOf' => [
                    ['type' => 'string', 'enum' => $permissions],
                    ['type' => 'string', 'pattern' => ExtensionPermissionRegistry::PERMISSION_PATTERN, 'description' => 'A permission registered by an enabled extension.'],
                ],
            ],
            'example' => ['websocket.connect', 'control.console'],
        ];
    }

    /** @return OpenApiSchema */
    private function adminEggConfigurationFilesSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => ['$ref' => '#/components/schemas/AdminEggConfigurationFile'],
            'example' => ['server.properties' => ['parser' => 'properties', 'find' => ['server-port' => '{{server.build.default.port}}']]],
        ];
    }

    /** @return OpenApiSchema */
    private function adminEggConfigurationFileSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'parser' => ['type' => 'string'],
                'find' => ['$ref' => '#/components/schemas/AdminEggConfigurationFind'],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function adminEggConfigurationFindSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => [
                'oneOf' => [
                    ['type' => 'string'],
                    ['type' => 'number'],
                    ['type' => 'boolean'],
                    ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
                ],
            ],
        ];
    }

    /** @return DeepOpenApiSchema */
    private function adminNodeConfigurationSchema(): array
    {
        return PterodactylDocumentation::schemaForValue([
            'debug' => false,
            'uuid' => '1b19cf3f-2f89-4f88-a81e-321e7fe326bc',
            'token_id' => 'abcd1234',
            'token' => 'secret',
            'api' => [
                'host' => '0.0.0.0',
                'port' => 8080,
                'ssl' => [
                    'enabled' => true,
                    'cert' => '/etc/letsencrypt/live/node.example.com/fullchain.pem',
                    'key' => '/etc/letsencrypt/live/node.example.com/privkey.pem',
                ],
                'upload_limit' => 100,
            ],
            'system' => [
                'data' => '/var/lib/pterodactyl/volumes',
                'sftp' => [
                    'bind_port' => 2022,
                ],
            ],
            'allowed_mounts' => ['/mnt/shared'],
            'remote' => 'https://panel.example.com',
        ]);
    }

    /** @return DeepOpenApiSchema */
    private function adminNodeDeployTokenSchema(): array
    {
        return PterodactylDocumentation::schemaForValue([
            'node' => 1,
            'token' => 'ptla_1234567890abcdef',
            'panel_url' => 'https://panel.example.com',
            'allow_insecure' => false,
        ]);
    }

    /** @return DeepOpenApiSchema */
    private function adminNodeSystemInformationSchema(): array
    {
        return PterodactylDocumentation::schemaForValue([
            'architecture' => 'amd64',
            'cpu_count' => 8,
            'kernel_version' => '6.8.0',
            'os' => 'linux',
            'version' => '1.11.0',
        ]);
    }

    /** @return DeepOpenApiSchema */
    private function adminNodeUtilizationSchema(): array
    {
        $metric = [
            'value' => '1,024',
            'max' => '102,400',
            'percent' => 1.0,
            'css' => 'green',
        ];

        return PterodactylDocumentation::schemaForValue([
            'disk' => $metric,
            'memory' => $metric,
        ]);
    }

    /** @return OpenApiSchema */
    private function adminExtensionSettingsSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'required' => ['registered', 'schema'],
                    'properties' => [
                        'registered' => ['type' => 'boolean', 'example' => true],
                        'schema' => [
                            'type' => 'array',
                            'items' => ['$ref' => '#/components/schemas/AdminExtensionSettingField'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function adminExtensionSettingFieldSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['input', 'label', 'help', 'tab', 'field', 'options', 'value', 'constraints', 'visibility'],
            'properties' => [
                'input' => ['type' => 'string', 'example' => 'curseforge_api_key'],
                'label' => ['type' => 'string', 'example' => 'CurseForge API Key'],
                'help' => ['type' => 'string', 'nullable' => true, 'example' => 'Leave blank to keep the stored key.'],
                'tab' => ['type' => 'string', 'nullable' => true, 'example' => 'CurseForge'],
                'field' => [
                    'type' => 'string',
                    'enum' => ExtensionSettingDefinition::FIELDS,
                    'example' => 'password',
                ],
                'options' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/components/schemas/AdminExtensionSettingOption'],
                ],
                'value' => [
                    ...$this->extensionSettingValueSchema(),
                    'nullable' => true,
                ],
                'constraints' => [
                    'type' => 'object',
                    'required' => ['max_length', 'max_items', 'max_kilobytes', 'accept'],
                    'properties' => [
                        'max_length' => ['type' => 'integer', 'nullable' => true, 'example' => null],
                        'max_items' => ['type' => 'integer', 'nullable' => true, 'example' => null],
                        'max_kilobytes' => ['type' => 'integer', 'nullable' => true, 'example' => null],
                        'accept' => ['type' => 'array', 'items' => ['type' => 'string', 'example' => 'image/png']],
                    ],
                ],
                'visibility' => [
                    'type' => 'string',
                    'enum' => ['admin', 'frontend', 'public'],
                    'example' => 'admin',
                ],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function adminExtensionSettingOptionSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['value', 'label'],
            'properties' => [
                'value' => $this->extensionSettingScalarSchema(),
                'label' => ['type' => 'string', 'example' => 'Recommended'],
            ],
        ];
    }

    /**
     * A setting's value: a scalar, or the list a multiselect or list field holds.
     *
     * @return array{oneOf: list<array{type: string, items?: array{oneOf: list<array{type: string}>}}>}
     */
    private function extensionSettingValueSchema(): array
    {
        return [
            'oneOf' => [
                ...$this->extensionSettingScalarSchema()['oneOf'],
                ['type' => 'array', 'items' => ['oneOf' => [['type' => 'string'], ['type' => 'number']]]],
            ],
        ];
    }

    /**
     * @return OpenApiSchema
     */
    private function extensionFieldsSchema(): array
    {
        $scalar = $this->extensionSettingScalarSchema()['oneOf'];

        return [
            'type' => 'object',
            'description' => 'Values of the fields extensions add to this resource, keyed by extension id and then by field name. Each extension validates the values sent for it; extensions left out of a request keep their values. Responses about one resource include them; lists leave them out.',
            'additionalProperties' => [
                'type' => 'object',
                'additionalProperties' => [
                    'oneOf' => [...$scalar, ['type' => 'array', 'items' => ['oneOf' => $scalar, 'nullable' => true]]],
                    'nullable' => true,
                ],
            ],
            'example' => ['billing' => ['plan' => 'pro']],
        ];
    }

    /**
     * Extension field values depend on the extensions installed, so the example response
     * cannot describe them; lists leave them out, so they are never required.
     *
     * @param  OpenApiSchemaInput  $schema
     */
    private function addExtensionFieldsToAttributesSchema(array &$schema): void
    {
        $properties = $schema['properties'] ?? [];
        JsonValueGuard::assertOpenApiSchemaMap($properties);
        $properties['extensions'] = ['$ref' => '#/components/schemas/ExtensionFields'];
        $schema['properties'] = $properties;

        $required = $schema['required'] ?? null;
        if (is_array($required)) {
            // SAFETY: an object schema's `required` lists its property names.
            $schema['required'] = array_values(array_diff(JsonValueGuard::stringList($required), ['extensions']));
        }
    }

    /**
     * @return array{oneOf: list<array{type: string}>}
     */
    private function extensionSettingScalarSchema(): array
    {
        return [
            'oneOf' => [
                ['type' => 'string'],
                ['type' => 'number'],
                ['type' => 'boolean'],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function adminSettingsSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['general', 'mail', 'advanced', 'meta'],
            'properties' => [
                'general' => [
                    'type' => 'object',
                    'required' => ['app:name', 'pterodactyl:auth:2fa_required', 'app:locale'],
                    'properties' => [
                        'app:name' => ['type' => 'string', 'example' => 'Pterodactyl'],
                        'pterodactyl:auth:2fa_required' => ['type' => 'integer', 'enum' => [0, 1, 2], 'example' => 0],
                        'app:locale' => ['type' => 'string', 'example' => 'en'],
                    ],
                ],
                'mail' => [
                    'type' => 'object',
                    'required' => [
                        'mail:default',
                        'mail:mailers:smtp:host',
                        'mail:mailers:smtp:port',
                        'mail:mailers:smtp:encryption',
                        'mail:mailers:smtp:username',
                        'mail:mailers:smtp:password',
                        'mail:from:address',
                        'mail:from:name',
                    ],
                    'properties' => [
                        'mail:default' => ['type' => 'string', 'example' => 'smtp'],
                        'mail:mailers:smtp:host' => ['type' => 'string', 'example' => 'mail.example.com'],
                        'mail:mailers:smtp:port' => ['type' => 'integer', 'example' => 587],
                        'mail:mailers:smtp:encryption' => [
                            'type' => 'string',
                            'nullable' => true,
                            'enum' => ['tls', 'ssl', null],
                            'example' => 'tls',
                        ],
                        'mail:mailers:smtp:username' => ['type' => 'string', 'nullable' => true, 'example' => 'panel@example.com'],
                        'mail:mailers:smtp:password' => ['type' => 'string', 'example' => ''],
                        'mail:from:address' => ['type' => 'string', 'example' => 'panel@example.com'],
                        'mail:from:name' => ['type' => 'string', 'nullable' => true, 'example' => 'Pterodactyl'],
                    ],
                ],
                'advanced' => [
                    'type' => 'object',
                    'required' => [
                        'recaptcha:enabled',
                        'recaptcha:secret_key',
                        'recaptcha:website_key',
                        'pterodactyl:guzzle:timeout',
                        'pterodactyl:guzzle:connect_timeout',
                        'pterodactyl:client_features:allocations:enabled',
                        'pterodactyl:client_features:allocations:range_start',
                        'pterodactyl:client_features:allocations:range_end',
                    ],
                    'properties' => [
                        'recaptcha:enabled' => ['type' => 'boolean', 'example' => false],
                        'recaptcha:secret_key' => ['type' => 'string', 'example' => ''],
                        'recaptcha:website_key' => ['type' => 'string', 'example' => 'website'],
                        'pterodactyl:guzzle:timeout' => ['type' => 'integer', 'example' => 15],
                        'pterodactyl:guzzle:connect_timeout' => ['type' => 'integer', 'example' => 5],
                        'pterodactyl:client_features:allocations:enabled' => ['type' => 'boolean', 'example' => false],
                        'pterodactyl:client_features:allocations:range_start' => ['type' => 'integer', 'nullable' => true, 'example' => null],
                        'pterodactyl:client_features:allocations:range_end' => ['type' => 'integer', 'nullable' => true, 'example' => null],
                    ],
                ],
                'meta' => [
                    'type' => 'object',
                    'required' => ['load_environment_only', 'show_recaptcha_warning'],
                    'properties' => [
                        'load_environment_only' => ['type' => 'boolean', 'example' => false],
                        'show_recaptcha_warning' => ['type' => 'boolean', 'example' => false],
                    ],
                ],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function adminVersionInformationSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['current', 'latest', 'is_latest', 'daemon', 'discord', 'donations'],
            'properties' => [
                'current' => ['type' => 'string', 'example' => '1.0.0'],
                'latest' => ['type' => 'string', 'example' => '1.0.0'],
                'is_latest' => ['type' => 'boolean', 'example' => true],
                'daemon' => ['type' => 'string', 'example' => '1.0.0'],
                'discord' => ['type' => 'string', 'example' => 'https://pterodactyl.io/discord'],
                'donations' => ['type' => 'string', 'example' => 'https://github.com/sponsors/pterodactyl'],
            ],
        ];
    }

    /** @return OpenApiSchema */
    private function errorEnvelopeSchema(bool $validation): array
    {
        $error = [
            'type' => 'object',
            'required' => ['code', 'status', 'detail'],
            'properties' => [
                'code' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'detail' => ['type' => 'string'],
            ],
        ];

        if ($validation) {
            $error['properties']['source'] = [
                'type' => 'object',
                'required' => ['field'],
                'properties' => [
                    'field' => ['type' => 'string'],
                ],
            ];
        }

        return [
            'type' => 'object',
            'required' => ['errors'],
            'properties' => [
                'errors' => [
                    'type' => 'array',
                    'items' => $error,
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array{description: string, name: string, endpoints: OutputEndpointData[]}>  $groupedEndpoints
     * @return array<string, string>
     */
    private function operationIds(array $groupedEndpoints): array
    {
        if ($this->operationIds !== null) {
            return $this->operationIds;
        }

        $bases = [];
        foreach ($groupedEndpoints as $group) {
            foreach ($group['endpoints'] as $endpoint) {
                $bases[$this->baseOperationId($endpoint)][] = $endpoint;
            }
        }

        $operationIds = [];
        foreach ($bases as $base => $endpoints) {
            foreach ($endpoints as $endpoint) {
                $operationIds[$this->endpointKey($endpoint)] = count($endpoints) === 1
                    ? $base
                    : $base.'For'.$this->pathSuffix($endpoint);
            }
        }

        return $this->operationIds = $operationIds;
    }

    private function baseOperationId(OutputEndpointData $endpoint): string
    {
        $uri = preg_replace('/\{([^}:]+):[^}]+\}/', '{$1}', $endpoint->uri) ?: $endpoint->uri;
        $uri = mb_trim($uri, '/');

        $authOperation = match (mb_strtoupper($endpoint->httpMethods[0]).' '.$uri) {
            'GET sanctum/csrf-cookie' => 'authGetCsrfCookie',
            'POST auth/login' => 'authLogin',
            'POST auth/login/checkpoint' => 'authCompleteLoginCheckpoint',
            'POST auth/logout' => 'authLogout',
            'POST auth/password' => 'authRequestPasswordResetEmail',
            'POST auth/password/reset' => 'authResetPassword',
            default => null,
        };
        if ($authOperation !== null) {
            return $authOperation;
        }

        if (mb_strtoupper($endpoint->httpMethods[0]) === 'DELETE') {
            if (preg_match('#^api/admin/servers/\{[^}]+\}$#', $uri) === 1) {
                return 'adminDeleteServer';
            }

            if (preg_match('#^api/admin/servers/\{[^}]+\}/force$#', $uri) === 1) {
                return 'adminForceDeleteServer';
            }
        }

        return $this->operationIdFromTitle($endpoint);
    }

    private function operationIdFromTitle(OutputEndpointData $endpoint): string
    {
        $title = $endpoint->metadata->title ?: $this->fallbackTitle($endpoint);
        // Titles are free text (extension endpoints especially) - anything but
        // word characters must not reach operationIds, which seed component
        // names and therefore $ref paths ("mods/modpacks" would emit refs the
        // client generator rejects).
        $title = preg_replace('/[^A-Za-z0-9]+/', ' ', $title) ?? $title;

        return $this->surface($endpoint).ucfirst(Str::camel($title));
    }

    private function fallbackTitle(OutputEndpointData $endpoint): string
    {
        $path = preg_replace('/\{[^}]+\}/', '', $endpoint->uri) ?: $endpoint->uri;

        return mb_strtolower($endpoint->httpMethods[0]).' '.$path;
    }

    private function surface(OutputEndpointData $endpoint): string
    {
        return match (true) {
            str_starts_with($endpoint->uri, 'auth/') => 'auth',
            $endpoint->uri === 'sanctum/csrf-cookie' => 'auth',
            str_starts_with($endpoint->uri, 'api/admin') => 'admin',
            str_starts_with($endpoint->uri, 'api/application') => 'application',
            str_starts_with($endpoint->uri, 'api/client') => 'client',
            default => 'api',
        };
    }

    private function pathSuffix(OutputEndpointData $endpoint): string
    {
        $path = preg_replace('#^(api/(admin|application|client)|auth)/?#', '', $endpoint->uri) ?: $endpoint->uri;
        $path = preg_replace('#^sanctum/#', '', $path) ?: $path;
        $path = preg_replace('/[{}?]/', '', $path) ?: $path;

        $suffix = Str::studly(str_replace('/', ' ', $path));

        return $suffix !== '' ? $suffix : ucfirst(mb_strtolower($endpoint->httpMethods[0]));
    }

    private function endpointKey(OutputEndpointData $endpoint): string
    {
        return mb_strtoupper($endpoint->httpMethods[0]).' '.$endpoint->uri;
    }

    private function arrayKeyString(int|string $key): string
    {
        // SAFETY: PHP array keys are restricted to integers and strings, both of which have lossless string forms for OpenAPI names.
        return (string) $key;
    }

    /**
     * @return array{resource: string, attributes: ApiPayload9, relationships?: ApiPayload9, collection: bool, paginated: bool, meta?: ApiPayload9, top_meta?: ApiPayload9}|null
     */
    private function fractalResponse(OutputEndpointData $endpoint): ?array
    {
        foreach ($endpoint->responses as $response) {
            if (! $response instanceof ExtractedResponse) {
                continue;
            }

            $status = $response->status;
            $status = $this->arrayKeyString($status);
            if (! str_starts_with($status, '2') || $status === '204') {
                continue;
            }

            $content = $response->content;
            if (! is_string($content)) {
                continue;
            }

            $decoded = json_decode($content, true);
            if (! is_array($decoded)) {
                continue;
            }

            JsonValueGuard::assertPayload9($decoded);

            if (($decoded['object'] ?? null) === 'list') {
                $item = $decoded['data'][0] ?? null;
                if (! is_array($item) || ! is_array($item['attributes'] ?? null)) {
                    continue;
                }

                $attributes = $item['attributes'];
                JsonValueGuard::assertPayload9($attributes);
                $relationships = $this->extractRelationships($attributes);
                $pagination = $decoded['meta'] ?? null;
                $fractal = [
                    'resource' => is_string($item['object'] ?? null) ? $item['object'] : 'resource',
                    'attributes' => $attributes,
                    'collection' => true,
                    'paginated' => is_array($pagination) && isset($pagination['pagination']),
                ];

                if (isset($item['meta']) && is_array($item['meta'])) {
                    JsonValueGuard::assertPayload9($item['meta']);
                    $fractal['meta'] = $item['meta'];
                }

                if (isset($decoded['meta']) && is_array($decoded['meta'])) {
                    $topMeta = $decoded['meta'];
                    unset($topMeta['pagination']);
                    if ($topMeta !== []) {
                        JsonValueGuard::assertPayload9($topMeta);
                        $fractal['top_meta'] = $topMeta;
                    }
                }

                if ($relationships !== null) {
                    $fractal['relationships'] = $relationships;
                }

                return $fractal;
            }

            if (is_string($decoded['object'] ?? null) && is_array($decoded['attributes'] ?? null)) {
                $attributes = $decoded['attributes'];
                JsonValueGuard::assertPayload9($attributes);
                $relationships = $this->extractRelationships($attributes);
                $fractal = [
                    'resource' => $decoded['object'],
                    'attributes' => $attributes,
                    'collection' => false,
                    'paginated' => false,
                ];

                if (isset($decoded['meta']) && is_array($decoded['meta'])) {
                    JsonValueGuard::assertPayload9($decoded['meta']);
                    $fractal['meta'] = $decoded['meta'];
                }

                if ($relationships !== null) {
                    $fractal['relationships'] = $relationships;
                }

                return $fractal;
            }
        }

        return null;
    }

    /**
     * @param  ApiPayload9  $attributes
     * @return ApiPayload9|null
     */
    private function extractRelationships(array &$attributes): ?array
    {
        $relationships = $attributes['relationships'] ?? null;
        unset($attributes['relationships']);

        if (! is_array($relationships) || $relationships === []) {
            return null;
        }

        JsonValueGuard::assertPayload9($relationships);

        return $relationships;
    }
}
