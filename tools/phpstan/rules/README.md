# Rules (PHPStan)

Opinionated PHPStan rules that reject low-evidence and low-signal PHP patterns. A port of [dmmulroy/anti-slop](https://github.com/dmmulroy/anti-slop) from Oxlint/TypeScript to PHPStan.

Every rule rejects the same move: erasing type evidence and then re-manufacturing it. The evidence-erasing tools in PHP are `mixed`, `object`, `array<K, mixed>`, inline `/** @var */`, `(cast)`, `assert()`, `is_*()` guards, reflection, dynamic member access, and class-loading mocks. The fix is always the same: parse external input once, at its I/O boundary, into a named domain type (a `final readonly class`, an enum, an `array{...}` shape) — [cuyz/valinor](https://github.com/CuyZ/Valinor) or [spatie/laravel-data](https://github.com/spatie/laravel-data) do the parsing step — and keep that type from the boundary to the point of use.

This directory is meant to be vendored, not treated as a fixed dependency. Copy it into your repository, read the rules, and change them to match your team's standards.

## Install

```bash
php tools/phpstan/rules/install.php /path/to/target-repo
```

Or by hand: copy this directory to `tools/phpstan/rules/`, then install the dependencies and register the autoload:

```bash
composer require --dev phpstan/phpstan phpstan/phpstan-strict-rules \
    phpstan/extension-installer spaze/phpstan-disallowed-calls symplify/phpstan-rules
```

```json
// composer.json
"autoload-dev": {
    "psr-4": { "Rules\\": "tools/phpstan/rules/src/" }
}
```

```neon
# phpstan.neon — level 10 with checkImplicitMixed is the intended base
includes:
    - tools/phpstan/rules/extension.neon
parameters:
    level: 10
    checkImplicitMixed: true
    excludePaths:
        - tools/phpstan/rules
        - .claude
        - .cursor
        - .agents
        - .codex
        - .windsurf
```

Keep every existing exclude, add any other agent-tooling directories your repository contains, and do not broadly exclude all dot-directories — some repositories keep owned source in them.

If `phpstan/extension-installer` would auto-enable these packages for analysis runs that should not inherit them, ignore them in the installer's config and let `extension.neon` include them explicitly:

```json
"extra": {
    "phpstan/extension-installer": {
        "ignore": [
            "phpstan/phpstan-strict-rules",
            "spaze/phpstan-disallowed-calls",
            "symplify/phpstan-rules"
        ]
    }
}
```

## Rules

### Generic rules (`rules.*`)

- `noMixedParameters` — rejects explicitly `mixed` parameters; functions carrying a `@phpstan-assert` tag for the parameter are boundary parsers and exempt.
- `noMixedReturns` — rejects `mixed` return contracts, including `Generator<..., mixed>` and `iterable<mixed>` yields; `never` and the magic methods `__get`/`__call`/`__callStatic`/`offsetGet` are allowed (PHP defines those contracts as dynamic).
- `noMixedTypeAliases` — rejects `@phpstan-type`/`@psalm-type` aliases that resolve to `mixed`, including unions and chains through other local aliases.
- `noObjectParameters` — rejects the broad `object` type on inputs; `@template T of object` keeps its evidence and is allowed.
- `noUnsafeDictionaryType` — rejects dictionaries (`array<K, V>`, `iterable<K, V>`, `list<V>`, `Traversable<K, V>`, `ArrayAccess<K, V>`) whose value type is `mixed`, `object`, an untyped `array`, `stdClass`, or a union containing one of those — in parameters, returns, and properties. Bare `array` stays with PHPStan level 6.
- `noRuntimeTypeChecks` — rejects `is_*()`, `gettype()`, `get_debug_type()`, `settype()` (via spaze/phpstan-disallowed-calls) and `instanceof` on `mixed` (custom); the configurable `rules.boundaryPaths` list and `@phpstan-assert` functions are exempt.
- `noDynamicAccess` — rejects `$obj->$name`, `$obj->{$expr}`, `$obj->$name()`, `$class::$name`, `[$obj, 'method']` callables (symplify + phpstan-strict-rules), and `call_user_func(_array)`, `ReflectionProperty::getValue/setValue`, `ReflectionMethod::invoke/invokeArgs`, `Closure::bind/bindTo/call` (spaze).
- `noClassLoadingMocks` — rejects mocks of concrete classes (`createMock`, `createStub`, `getMockBuilder`, `Mockery::mock`, Laravel `$this->mock()`/`partialMock()`/`spy()`), Mockery `overload:`/`alias:` prefixes, and uopz/runkit runtime patching. Mocks of interfaces and abstract classes are fine.
- `requireSafetyCommentForAssertion` — requires a `SAFETY:` comment on every inline `@var`, narrowing `assert()`, cast, and Webmozart `Assert::*` call. A cast that does not change the type is dead code, not an assertion, and is not flagged.
- `noChainedAssertions` — rejects cast chains (`(object) (array) $x`) and consecutive inline `@var` docblocks re-narrowing the same variable.
- `noKnownValueWidening` — rejects broad declared types on properties and returns whose values are known literals; the inline `@var` half is PHPStan's own `reportAnyTypeWideningInVarTag`, which the extension enables.
- `noWidenThenAssert` — rejects flows that widen a binding (`(array)`/`(object)` cast, `json_decode(json_encode(...))`, `get_object_vars`, `iterator_to_array`, `data_get`/`Arr::get`, broad inline `@var`) and later narrow it back with an assertion form.
- `noConditionalEmptyArraySpread` — rejects `...($c ? ['k' => $v] : [])` and the same dodge through `array_merge`/`array_replace`/`+`.
- `noForbiddenTermsInSymbolNames` — rejects the configurable `rules.forbiddenSymbolTerms` (default: `shape`) as a case-insensitive substring in class, method, function, property, parameter, variable, constant, enum-case, and `@phpstan-type` names.

### Laravel rules (`rulesLaravel.*`, opt-in)

Include `laravel/extension.neon` only in Laravel projects (requires larastan):

- `noServiceConstruction` — rejects `new Service(...)`, `app()`, `resolve()`, `App::make()`, and `$container->make()` for classes under `rulesLaravel.serviceNamespaces` (default `App\Services\`, `App\Actions\`, `App\Repositories\`) outside providers, tests, factories, and the service's own static constructors.

`canvural/larastan-strict-rules` was evaluated and deliberately not bundled: its facade / global-helper / dynamic-where bans are whole-app architecture policy beyond this set's scope. Add it yourself if your team wants that policy.

## Violation examples

### `noMixedParameters`

```php
function handle(mixed $input): void {}
```

### `noMixedReturns`

```php
function loadUser(): mixed { return $this->find(); }
```

### `noMixedTypeAliases`

```php
/** @phpstan-type ExternalValue mixed */
```

### `noObjectParameters`

```php
function save(object $value): void {}
```

### `noUnsafeDictionaryType`

```php
/** @param array<string, mixed> $metadata */
function tag(array $metadata): void {}
```

### `noRuntimeTypeChecks`

```php
if (is_string($input)) { useName($input); }
```

### `noDynamicAccess`

```php
$value = $owner->$key;
```

### `noClassLoadingMocks`

```php
$store = $this->createMock(UserStore::class); // UserStore is concrete
```

### `requireSafetyCommentForAssertion`

```php
$userId = (int) $value;
```

Add a specific justification immediately before a necessary assertion:

```php
// SAFETY: the route pattern above only admits digit strings.
$userId = (int) $value;
```

### `noChainedAssertions`

```php
$user = (object) (array) $input;
```

### `noKnownValueWidening`

```php
/** @var array<string, mixed> */
public array $handlers = ['start' => 'startHandler'];
```

### `noWidenThenAssert`

```php
$widened = (array) $payload;
/** @var array{id: string} $widened */
```

### `noConditionalEmptyArraySpread`

```php
$options = [...($timeout !== null ? ['timeout' => $timeout] : [])];
```

### `noForbiddenTermsInSymbolNames`

```php
interface UserShape {}
```

### Laravel: `noServiceConstruction`

```php
return new \App\Services\ReportBuilder;
```

Inject the service through the constructor and let the container own the wiring.

## Development

```bash
vendor/bin/phpunit -c tools/phpstan/rules/phpunit.xml
vendor/bin/phpstan analyse -c tools/phpstan/rules/phpstan.neon
```

The second command runs the rule set against its own source at level 10; it must stay clean.
