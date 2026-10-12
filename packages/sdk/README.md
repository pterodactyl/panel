# @pterodactyl/sdk

Build extensions and themes for the Pterodactyl panel (v2+). This package provides:

- **Types** for the runtime API. The implementation is provided *by the panel*
  through its import map, so your built extension never bundles the SDK, React, or
  TanStack Query: it shares the panel's own instances.
- **The Vite build presets** (`@pterodactyl/sdk/vite`): `defineExtensionConfig` keeps those
  modules external and emits `dist/client.js` plus lazy screen chunks; `defineThemeConfig`
  also builds a theme's stylesheet.
- **Test helpers** (`@pterodactyl/sdk/testing`, `@pterodactyl/sdk/vitest`) that render views,
  UI primitives and screens with the real runtime and no backend.
- **The `pterodactyl-theme` CLI**, which lists the panel's views, ejects the stock theme's source
  for one, and prints its skins.

Extensions start at [Quick start](#quick-start). Themes start at [Themes](#themes).

## Quick start

```tsx
// src/client/index.tsx
import { useState } from 'react';
import { Button, definePterodactylExtension, toast } from '@pterodactyl/sdk';

export default definePterodactylExtension({
    setup({ slots, screens }) {
        slots.register('server.console.before', () => {
            const [count, setCount] = useState(0);
            return <Button onClick={() => { setCount(count + 1); toast.success('hi'); }}>Clicked {count}</Button>;
        });

        screens.register('votes', () => import('./VotesScreen'));
    },
});
```

```js
// vite.config.mjs
import { defineExtensionConfig } from '@pterodactyl/sdk/vite';

export default defineExtensionConfig({ entry: 'src/client/index.tsx' });
```

`vite build` emits `dist/client.js`, referenced from your `extension.json`:

```json
{
    "id": "myext",
    "name": "My Extension",
    "version": "1.0.0",
    "ui": {
        "entry": "dist/client.js",
        "mode": "native",
        "screens": [{
            "id": "votes",
            "area": "server",
            "path": "votes",
            "nav": { "label": "Votes" },
            "permission": ["ext.myext.view"]
        }]
    }
}
```

The `ext.myext.view` permission above is the extension's own subuser permission. Register it from
the extension's PHP provider so subusers can be granted it in the server's user editor:

```php
$this->registerPermissions('Manage server votes.', ['view' => 'View the votes page.']);
```

Each key becomes `ext.<id>.<key>`. Check it on the backend with `$user->can('ext.myext.view', $server)`.

Everything exported from `@pterodactyl/sdk` (and the shared `react` / `react-dom` /
`@tanstack/react-query` import-map entries) is semver-governed ABI: additions are
minor versions, removals are majors.

## Loading and recovery

Screen IDs, paths, navigation, and permissions live in `ui.screens` in the manifest. A screen's `permission` list names the subuser permissions needed to open it and is only accepted on `server` screens; account and admin screens are open to every signed-in user and every root admin respectively. Register each declared screen with `screens.register(id, importer)` during synchronous `setup`. Paths use a static first segment and may include named parameters such as `votes/$tab` (the panel reserves `$id`); duplicate paths, duplicate parameter patterns, and core route collisions reject the offending extension. Screen components receive a `data` prop (`ScreenComponentProps`) with the current `pathname`, `search`, and `params`, so `votes/$tab` reads its value from `data.params.tab`.

The panel renders before optional bundles finish importing. Each extension commits atomically when ready; slot display order follows the configured extension order. Screens show local loading state while their bundle or chunk loads. Slots have individual Suspense and error boundaries, and SDK websocket callbacks are isolated and attributed to their mod. Native extensions execute in the panel's JavaScript environment; boundaries are not a sandbox and cannot interrupt synchronous code.

Recoverable render/query failures offer Retry. Failed screen chunks offer an explicit page reload because resetting an error boundary cannot evict a rejected browser module. Enabling, disabling, updating, or changing frontend configuration takes effect on the next full page load; reload after management changes. Already evaluated JavaScript is not unloaded dynamically.

## Types and shared dependencies

The panel supports the React 19.3 client API. Server-only caching APIs, development-only `captureOwnerStack`, and unstable cache-refresh APIs are not part of the shared React facade. React, React DOM, JSX runtime, Query, and SDK entry points stay external through the Vite preset.

SDK declarations are generated from the runtime public surface with `npm run sdk:generate` at the panel root. `npm run sdk:check` checks reproducibility and compiles a packed-package consumer, including negative type cases. Component props, form fields, and `useCurrentServer(server => server.attributes.name)` preserve their actual types. SDK peer dependencies include the existing libraries referenced by those declarations; use SDK form hooks/components to share the panel's bound form contexts.

Declare screen metadata in the manifest and register each implementation by ID with `screens.register`. Build extensions against the SDK version used by the panel.

A `file:` dependency on `packages/sdk` is a link into the panel checkout. TypeScript follows links to their real location by default, so the SDK declarations would take React, TanStack Query and the other peers from the panel's `node_modules` while your code takes them from yours; when the versions differ, `useQuery(serverFileContentQueryOptions(...))` fails on two unrelated `QueryClient` types. Scaffolded extensions set `"preserveSymlinks": true` in `tsconfig.json`, which resolves the SDK's peers from the extension, the same way the Vitest preset does at runtime. Do not add `paths` entries for `react`, `@tanstack/react-query` or `lucide-react`. An SDK installed from a tarball or registry needs neither option.

## Resource hooks and native data

Admin screens may declare `parent: "admin.node"`, `"admin.server"`, `"admin.egg"`, or `"admin.user"`.
Their paths become detail tabs and `useCurrentResource()` supplies the typed resource.
`nav` supports `order`, `group`, `badge`, and `icon`. Parameterized navigation
requires `nav.params`, for example `{ "tab": "overview" }` for `votes/$tab`.
`PanelLink`, `usePanelNavigate`, and `usePanelLocation` use the host router; navigation
by extension id and screen id also works for resource tabs.

`serverQueryOptions`, `serverFilesQueryOptions`, `serverFileContentQueryOptions`,
`serverStartupQueryOptions`, and `serverBackupsQueryOptions` use core cache keys and
transport. Pass the server UUID for files, startup, and backups to share core entries.
`serverLogsQueryOptions(uuid, lines?)` reads the latest console output (at most 100
lines, oldest first) and needs the `websocket.connect` permission.
Their hooks accept selectors, and `invalidateServerData(uuid, domains)` returns the
host invalidation promise. Curated mutation hooks reuse core updates and serialize
startup writes. Await `mutateAsync` from a handler wrapped with `useExtensionAction`.

`serverResourcesQueryOptions(uuid)` and `useServerResources(uuid, select?)` read power
state and resource usage from the cache entry the dashboard's server cards poll, so a
screen that shows them adds no request of its own. The panel asks Wings at most every 20
seconds; inside the server area, `useServerWebsocketEvent('stats' | 'status', ...)`
delivers live values. `invalidateServerData(uuid, ['resources'])` refetches the entry.
`useSendServerPower(uuid)` takes `{ signal: 'start' | 'stop' | 'restart' | 'kill' }` and
`useSendServerCommand(uuid)` takes `{ command }`. Both go through the client API, so they
work without a console socket, are checked against the user's `control.*` permissions by
the panel, and report failures with the panel's own notification before rejecting.

`useNavigationBlocker(shouldBlock, options?)` asks before unsaved work is left behind:
router links and `usePanelNavigate`, the browser's back and forward buttons, and closing
or reloading the tab. Pass a boolean, or a function read at navigation time when the
answer lives in a ref. `message` sets the text of the default confirmation,
`beforeUnload: false` leaves tab close alone, and `confirm(transition)` replaces the
confirmation with your own decision (resolve `true` to leave); it receives the current
and next `pathname`, `params` and `search`, so navigation within your own screen can pass.

```tsx
const [dirty, setDirty] = useState(false);
useNavigationBlocker(dirty, { message: 'Discard your changes to this file?' });
```

File toolbar, row actions, and selection actions receive typed files, directory,
selection, and refresh callbacks. `server.startup.form` receives current configuration
and a guarded Docker image setter. `panel.users.detail.form` supplies the native
`form`, so contributed `form.AppField` controls share core validation, pending state,
submission, and persistence. Keep values in that form rather than copying a separate draft. To add fields of your
own to an admin form, see [Admin form fields](#admin-form-fields). `columns.register('admin.nodes', { id, label,
component })` adds a typed cell to the native table; servers and eggs are also supported.
Columns commit atomically and render inside local error boundaries. Table, Tooltip,
DropdownMenu, and Pagination complement the existing form and dialog primitives. `Empty`
(with `EmptyHeader`, `EmptyMedia`, `EmptyTitle`, `EmptyDescription`, `EmptyContent`)
matches the panel's empty states; pass it as a `Table`'s `emptyState`.

`useExtensionTranslation('messages')` loads the provider's registered
`resources/lang/<locale>/messages.php` group. Extension CSS is built with the extension's own
Tailwind prefix (see [Styling](#styling)). The Vite preset waits for entry CSS before setup.

The active theme sets the panel's CSS tokens (see [Tokens](#tokens)). Extension utilities read
the same live tokens (`hw:bg-card`, `hw:w-sidebar`, `hw:p-4` for the prefix `hw`), so an
extension follows the theme without knowing it. The console and the `theme-color` meta re-read
their tokens when a stylesheet or an inline property on `<html>` changes; `onThemeChange(listener)`
gives canvas or chart code the same signal, `readThemeToken('--name')` reads a value, and
`notifyThemeChange()` reports changes made through the CSSOM. Only the panel's own `theme-color`
tag follows the token; one registered with `registerHeadTags` is left as rendered.

## Conditional screens, badges, and icons

A screen's `when` states where it exists. A screen whose conditions fail is absent from
navigation and not routable: its URL shows the panel's not-found page.

```json
{
    "id": "mods",
    "area": "server",
    "path": "mods",
    "nav": { "label": "Mods", "icon": "blocks", "badge": "Beta" },
    "when": {
        "eggTags": { "any": ["minecraft"] },
        "eggFeatures": { "any": ["eula"] },
        "match": "any"
    }
}
```

`eggFeatures` and `eggTags` are for `server` screens. Each takes `all` (every value
present) and/or `any` (at least one), compared without regard to case against the
server's `egg_features` and `egg_tags`. With both declared they must both pass unless
`match` is `"any"`. These rules come from the manifest and the server payload, so they
apply before your bundle has loaded. Egg ids are deliberately not matchable: they differ
between installations, so decide those cases at runtime from your own configuration.

For anything the manifest cannot express, declare `"when": { "runtime": true }` (any
area, alone or beside egg rules) and pass a predicate when registering the screen:

```tsx
function useHasDomains({ server }: ScreenContext) {
    return useSuspenseQuery(domainsQueryOptions(server!.attributes.uuid)).data.length > 0;
}

// The third argument is a ScreenOptions object.
screens.register('subdomains', () => import('./SubdomainsScreen'), {
    visible: useHasDomains,
    badge: ({ config }) => (config.maintenance === true ? 'Paused' : undefined),
});
```

`visible` and `badge` receive a `ScreenContext` (`user`, `config`, the `server` on server
screens, the `resource` on admin resource tabs) and are called while rendering their own
isolated mount, so they may use hooks and suspend; name them `use…` when they do. They
re-run whenever that data changes. A predicate that throws hides the screen, a badge that
throws leaves the manifest badge in place, and both are reported against your extension
without disturbing the rest of the navigation.

A `runtime` screen stays hidden until its bundle has loaded and the predicate has
returned `true`, and its URL shows a spinner in the meantime; entries are never listed
first and withdrawn later. That is why the flag lives in the manifest: registering
`visible` for a screen without it, or omitting `visible` for a screen with it, fails the
extension at load. Egg rules are checked first, so a predicate only runs where they pass.

`badge` needs no manifest flag. Return a string or number to show it (32 characters at
most), `null` or `''` for no badge, and `undefined` to keep `nav.badge`, which also shows
while the bundle loads or the badge suspends.

`nav.icon` accepts any [lucide](https://lucide.dev/icons) icon name in kebab-case, such
as `life-buoy`. The names this panel ships are listed in `@pterodactyl/sdk/icons.json`;
a name outside that list renders the default icon and is reported by
`p:extension:doctor`. `<NamedIcon name="life-buoy" />` renders the same icons from the
panel's copy of lucide, so an icon chosen as data (a setting, an API field) does not
require bundling the icon set; `loadIconNames()` resolves the available names for a
picker. Common names render immediately; the rest of the set is one lazily fetched chunk
shared by every extension. Keep importing icons you reference in code from
`lucide-react` and pass them to `<Icon icon={…} />`.

The top-level `icon` is shown for the extension on the admin extensions page, in place of
its initials. It is either a lucide icon name, as above, or the path of a PNG, JPEG or
WebP image inside the package, such as `"icon": "icon.png"`. Images may be at most 512 KB
and 2048 px per side, and a square image of 128 px or more looks best. The image does
not have to sit in `dist`: it is served to administrators from the package itself, so it
shows while the extension is disabled, and `p:extension:pack` adds it to the archive.
`p:extension:doctor` warns about an image that is missing or unreadable.

```json
{ "id": "mods", "name": "Mods", "version": "1.0.0", "icon": "icon.png" }
```

## Author tooling and configuration

Use `p:extension:doctor <path>`, `p:extension:dev <path> --watch`, and
`p:extension:pack <path>` for preflight checks, successful-build publishing with browser reload in debug mode, and
runtime-only archives. Scaffolds include tests, CI, OpenAPI generation, and styling.
`@pterodactyl/sdk/testing` supplies `createExtensionTestHost` and `createTestServer`;
`@pterodactyl/sdk/vitest` supplies aliases and setup for the real runtime. Unmount the
wrapper and dispose the host after each test. The test host shares the real cache;
create one host at a time within a test environment.

Declare `requires.panel`, `requires.sdk`, `requires.php`, and `requires.extensions` as Composer-style
version constraints in the manifest. Incompatible extensions and dependency cycles
fail independently. Activation validates the build and runs migrations before enabling
metadata. Upgrades retain old asset chunks for open sessions. Failed activation restores
the previous package and asset pointer; partially applied database migrations need manual
recovery. Successful lifecycle changes invalidate routes and signal queue worker restarts;
reload the page to replace already evaluated frontend code.

Migrations live in `database/migrations`. The panel registers that directory with
Laravel's migrator for every enabled extension, so `migrate:status` lists the migrations
and `migrate:rollback` finds them; a provider does not need to call
`loadExtensionMigrations()`. Laravel records a migration by its file name, so enabling
refuses an extension with a migration named like one of the panel's or another installed
extension's, and `p:extension:doctor` reports it.

A provider reads its settings with `$this->settings()`. Code outside the provider, such as
a controller or a job, uses the `Pterodactyl\Facades\Extensions` facade:
`Extensions::settings('billing')` returns the same settings, and
`Extensions::isAvailable('billing')` tells whether an extension is enabled and running.
Both return an `ExtensionSettings`: `get($key, $default)`, `set($key, $value)`, and
`setSecret($key, $value)`, which stores the value encrypted.

Backend settings support `forUser($user)` and `forServer($server)`. Mark secrets with
`->secret()` to encrypt storage, mask admin output as `********`, and prohibit frontend
exposure; `->field('password')` is always secret. A secret submitted empty, as its mask,
or not at all keeps the stored value, whatever its field.
`->frontend()->frontendType('boolean')` declares and verifies a public value's type. `p:extension:types <path>` generates literal screen ids, registered permissions, and public config
keys. `defineConfiguredExtension` accepts a parser for runtime configuration and these
generated types. Undeclared value types remain bounded JSON.

Frontend values reach signed-in users only. `->frontend()->public()` also delivers a
value to visitors who are not signed in (the login page), so use it only for values
anyone may read; `public()` requires `frontend()` and cannot be combined with
`secret()`. Generated types add `ExtensionPublicConfigKey` and `ExtensionPublicConfig`
for the keys a guest receives; every other key is absent until the user signs in and
the page reloads.

Submitted values are checked against their field on the server as well as against the
definition's rules: `text` and `password` take a string or number, `number` a number,
`toggle` a boolean, and `select` one of its option values (compared strictly, so `'25'`
does not match `25`). `null` passes all of them. A `number`, `toggle` or `select` default
has to pass the same check.

Beyond `text`, `password`, `number`, `toggle`, and `select`, settings have five typed
fields, each validated server-side and rendered by the admin Extensions form:

- `->color()`: a hex colour (`#rgb`, `#rgba`, `#rrggbb`, `#rrggbbaa`) or a numeric
  `oklch(L C H [/ A])` colour, stored lower case. Nothing else is accepted, so the value
  is safe to place in a style rule. Clearing it restores the default.
- `->textarea(maxLength: 5000)`: a multi-line string with normalized line endings.
- `->multiselect($options)`: a list of the declared string or integer option values, in
  declaration order.
- `->list(itemRules: ['max:255'], maxItems: 50)`: an ordered list of strings; every item
  must pass the item rules.
- `->file(mimes: ExtensionSettingFiles::IMAGES, maxKilobytes: 1024)`: an admin upload.
  The type is decided from the content against the allow-list (PNG, JPEG, GIF, WebP and
  AVIF by default; ICO, WOFF, WOFF2 and SVG only when listed). An SVG must be plain SVG:
  scripts, event handlers, animation, embedded documents, `javascript:` or non-image
  `data:` links, and elements or attributes from other XML vocabularies (editor metadata
  included) are refused. The file is stored under a random name on the
  `extensions.files_disk` disk, which must be private (a public disk is refused, so files
  are only served with the panel's sandboxing headers), and `get()` and `ctx.config`
  return its URL under `/extension-files/<id>/` or the default (a URL or `null`).
  Replacing or clearing a file deletes the old one; removing the extension deletes them
  all. Files are uploaded through
  `POST`/`DELETE /api/admin/extensions/<id>/settings/<input>/file`, never through a
  settings update.

These fields imply their `frontendType` (`string` or `array`; a colour or file with a
`null` default stays JSON) and none of them can be `->secret()`.

`->tab('Storage')` puts a field in a section of the admin form, chosen from a dropdown
above the form; sections appear in the order their first field is defined. Once any
field names a tab, fields without one are shown under "General", which comes first. A
form with no tabbed fields has no dropdown. Sections only group the form: every field is
saved together, and keys and validation are unchanged.

The package exports `manifest.schema.json` for editor completion. Frontend scaffolds
reference it with `$schema`; the server validates every package before activation.

Use `ExtensionJobProgress::begin($extensionId, $user, $server, $permission)` to create
a namespaced job snapshot, then `update($extensionId, $id, $percent, $message, $status)`
from workers. User jobs are private to their creator and root admins. Server jobs with
an extension permission are readable by users who still have that permission; otherwise
they remain creator-only. `useExtensionJobProgress(id, { serverUuid })` polls running
jobs, recovers on reconnect, and stops when the job finishes or access expires. Use a
shared cache store for workers and web requests. Snapshots expire after
`extensions.progress_retention_seconds` (one hour by default); they are progress UI,
not a durable job audit log.

## Admin form fields

An extension can add its own fields to the admin create and edit forms of users, servers,
nodes, eggs, locations, mounts and database hosts. The panel validates them with the form,
saves them in the same transaction, and returns them on the resource.

Declare the fields in PHP with a `Fields` class and register it from the provider, one
class per model:

```php
use Illuminate\Validation\Rule;
use Pterodactyl\Extensions\Fields;
use Pterodactyl\Models\User;

$this->registerFields(User::class, UserRole::class);

final class UserRole extends Fields
{
    public function rules(): array
    {
        return ['role' => ['nullable', 'integer', Rule::exists(Role::class, 'id')]];
    }

    public function values(User $user): array
    {
        return ['role' => RoleAssignment::query()->whereKey($user->id)->value('role_id')];
    }

    public function save(User $user, array $values): void
    {
        RoleAssignment::query()->updateOrCreate(['user_id' => $user->id], ['role_id' => $values['role']]);
    }
}
```

Every method is optional. The panel calls them through the container, like the methods of
a FormRequest, so each can type-hint the model it extends and any other dependency. A model
parameter always receives the model being changed, never the signed-in user, and every
method but `values()` and `save()` also runs while the model is created, so a model
parameter there must be nullable. Registering a class that breaks either rule fails the
provider's boot.

- `rules()` validates the extension's values on their own, so `required_if:plan,pro` refers
  to the extension's own `plan`. They run for every extension the signed-in user may change
  when the model is created, whether or not the request sends its values, and when an
  update includes the extension under `extensions`, even with no values. The admin forms
  include every extension they show, so `required` holds there on create and on every
  edit, like the core fields beside it. An update that leaves the extension out leaves its
  values alone. A `?User $user` parameter receives the model when updating and null when
  creating.
- `values($model)` returns the current values, keyed by field. Values are strings, numbers,
  booleans, null, or lists of those.
- `save($model, array $values)` stores the validated values; the parameter must be named
  `$values`. It runs inside the transaction that writes the model, after its row is written
  and before Wings is contacted, so throwing rolls the whole change back. Put work that has
  to wait for the commit in `DB::afterCommit()`.
- `authorize()` decides whether the signed-in user may see and change the values; type-hint
  `#[CurrentUser] User $admin` to get them. A request that sends values it refuses gets a 403.
  Only root admins reach these endpoints, and `$admin->can()` passes every check for a root
  admin, so decide on something else, such as the admin's id or a role the extension keeps.
- `secrets()` lists the fields that hold credentials. Their values are encrypted when the
  panel stores them and returned as `********`. A save that sends the mask, or leaves the
  field out, keeps the stored value, and `save()` receives it. A save that sends null or an
  empty string clears it, so a `required` rule then fails. An empty secret is returned as
  null, so sending back what the form loaded clears nothing.
- `attributes()` and `messages()` name the fields in validation messages and replace them,
  as on a FormRequest.

Leave out `values()` and `save()` and the panel stores the values for you, apart from the
extension's other settings: read them with `$this->settings()->fields($user)`. Settings from
`for()`, `forUser()` and `forServer()` never include them, so an extension that shows users
their own settings cannot expose admin fields. Implement both to keep the values in your
own tables, where you can index, join and query them. Implementing only one fails the
provider's boot.

The admin API's create and update endpoints accept `extensions: { "<id>": { "<field>": value } }`
and report errors under `extensions.<id>.<field>`. Responses about one resource include
`attributes.extensions`; lists leave it out. A request that leaves an extension out does not
run its `save()`, values sent for an extension that is disabled are ignored, and an extension
whose `values()` throws is recorded as failing and left out of the response. One whose
methods throw while a save validates its values is recorded as failing too, and the request
gets a 422 under `extensions.<id>`. Mark the class
`#[ApplicationApi]` (`Pterodactyl\Extensions\Attributes\ApplicationApi`) to accept and return
its values on the Application API's user, node, location and server endpoints as well, so a
billing system that provisions through it can set them.

Draw the fields with `forms.extend()`. The component receives `field(name)`, which binds one
field to the form: `value`, `setValue`, an `id` for its label, and `error`, the panel's
message from the last save until the field changes. `values` holds every field of the
extension: its saved values with the edits made so far. `mode` is `'create'` or `'edit'`,
and `resource` is the saved resource when editing.

```tsx
import { useQuery } from '@tanstack/react-query';
import { definePterodactylExtension, http, Label, Select, type FormExtension } from '@pterodactyl/sdk';

const RoleSelect: FormExtension<{ role: number | null }, 'admin.user'> = ({ field }) => {
    const role = field('role');
    const { data: roles = [] } = useQuery(rolesQuery);

    return (
        <div>
            <Label htmlFor={role.id}>Role</Label>
            <Select
                id={role.id}
                value={role.value ?? 0}
                options={[{ value: 0, label: 'No role' }, ...roles.map((r) => ({ value: r.id, label: r.name }))]}
                onChange={(value) => role.setValue(value === 0 ? null : Number(value))}
            />
            {role.error && <p className={'input-help error'}>{role.error}</p>}
        </div>
    );
};

export default definePterodactylExtension({
    setup({ forms }) {
        forms.extend('admin.user', RoleSelect);
    },
});
```

The forms are `admin.user`, `admin.server` (the create form and the details form),
`admin.node`, `admin.egg`, `admin.location`, `admin.mount` and `admin.database_host`. The
panel draws each extension's component under its name, inside an error boundary. Every save
sends all values of every extension it draws, edited or not, so their rules run each time
and `save()` receives the complete values. When editing, an extension whose values the
panel did not return is not drawn or sent, so a save cannot write over them.

## Backend provider API

The manifest's `provider` class is loaded through a map of PSR-4 prefixes to package
directories, such as `{ "Acme\\Billing\\": "src" }`. Scaffolds keep it in `composer.json`
under `autoload.psr-4`, where Composer, editors and static analysis read it too; an
`autoload` map in `extension.json` takes its place when present. A prefix cannot equal, contain
or sit inside `Pterodactyl\`, `Illuminate\`, `Laravel\`, `Symfony\` or a namespace of the
panel's Composer packages, and enabling fails while another enabled extension autoloads an
overlapping prefix. Extension class loaders are registered behind the panel's, so once an
extension's `vendor/autoload.php` has returned, a class the panel or its packages provide
always comes from the panel.

Composer names the class `vendor/autoload.php` declares after the lock file's hash unless
`config.autoloader-suffix` sets it, so two packages installed from the same requirements
declare the same class, and PHP cannot recover from declaring it twice. The panel refuses
to enable or load an extension whose suffix the panel or another enabled extension already
uses. Give each extension a suffix of its own in its `composer.json`:

```json
{ "config": { "autoloader-suffix": "AcmeBillingExtension" } }
```

Build `vendor/` with `composer install --no-dev --optimize-autoloader` before packing.
`p:extension:doctor` and `p:extension:pack` read `vendor/composer/installed.json` and warn
about development packages left in `vendor/`, about `composer.json` requirements the
panel's copy of a package does not satisfy, and about bundled packages the panel loads at
another version. None of these stop the extension from loading, but its code runs with the
panel's copy of any package both ship.

Providers can use `listenToServerOperations` for immutable `provision`, `install`,
`reinstall`, `backup`, `delete`, `suspend`, `unsuspend`, and `transfer` results. Results
dispatch after commit and carry identifiers only (`serverUuid`, `operation`, `successful`,
`resourceUuid`): after `delete` the server row is gone, so key your own data by server
uuid. Provisioning fires only when Wings accepts creation, `delete` also fires when a
failed provisioning is cleaned up, suspension fires only for a change Wings accepted, and
`transfer` reports both outcomes. Listener failures are attributed to the extension. The
`backup completed` websocket event carries the backup's `uuid` in its payload.

### Wrapping core actions

`wrapAction($contract, $decorator)` runs extension code around any action contract under
`Pterodactyl\Contracts\` that the panel binds per caller (not as a shared singleton). The
wrapper runs around every call to that contract, including the calls core makes internally,
such as `DeleteServer` dropping databases through `DeletesDatabases`. The decorator receives
the implementation that would otherwise be used and returns an implementation of the same
contract:

```php
$this->wrapAction(DeletesServers::class, fn (DeletesServers $inner): DeletesServers => new PurgeDnsRecords($inner, $this->app->make(DnsRecords::class)));

final class PurgeDnsRecords implements DeletesServers
{
    public function __construct(private readonly DeletesServers $inner, private readonly DnsRecords $dns) {}

    public function withForce(bool $bool = true): self
    {
        $this->inner->withForce($bool);

        return $this;
    }

    public function delete(Server $server): void
    {
        $serverId = $server->id;
        $this->inner->delete($server);
        // The core action returned, so the server is gone. Use DB::afterCommit() for
        // work that must also wait for a transaction opened by the caller.
        DB::afterCommit(fn () => $this->dns->purge($serverId));
    }
}
```

These core actions order their work as follows, and wrappers of the contracts they call
run outside their transactions:

- `InitiatesBackups` creates the backup row in a transaction, then asks Wings to start the
  backup. A backup Wings rejects is stored as failed. Backups rotated out to make room are
  deleted through `DeletesBackups` only after Wings accepts the new one; a failed rotation
  delete is reported, not thrown.
- `DeletesServers` deletes the panel rows in a transaction, then drops each remote database
  through `DeletesDatabases`. The `Database` it passes is already deleted from the panel. A
  failed drop is reported and the rest are dropped.
- `InitiatesTransfers` calls Wings after the transfer records commit. A transfer Wings
  rejects is marked failed through `FailsTransfers`.
- `RotatesDatabasePasswords` logs the rotation after its transaction around the remote user
  change commits.

The wrapper owns the call: it decides whether and when to invoke the inner action and
must forward fluent options such as `withForce()` itself, returning itself. Whatever it
throws reaches the caller unchanged, and exceptions of the inner action pass through
untouched. A reported exception raised from the extension's package is recorded against
the extension. The decorator is skipped while the extension is disabled or failed to boot;
one that throws or returns another type is recorded and skipped, so the core action always
resolves. Extensions wrap in load order, the last one outermost. The actions that install,
enable, disable, remove and configure extensions (`Contracts\Extensions\*`) and activate or
reset themes (`Contracts\Themes\*`) cannot be wrapped; asking for one fails the provider's boot.

Core controllers resolve these contracts, not the classes behind them. Renaming a server from the client API calls
`UpdatesServerDetails`. Toggling a backup lock from the admin API calls `TogglesBackupLocks`.
Deleting an allocation block calls `DeletesAllocations` once per allocation. Disabling
two-factor, by an admin or by the user, calls `DisablesTwoFactor`, which clears the TOTP
secret. Changing a password, from the account page or with a reset link, calls
`UpdatesUserPasswords`. Changing a Docker image calls `UpdatesServerDockerImage`, both from the client
endpoint and when an admin startup update (`UpdatesServerStartup`) sets the image. Changing a
startup variable calls `UpdatesServerVariable`, once from the client endpoint and once per
variable in a startup update.
`UpdatesEggs`, `UpdatesEggInstallScripts` and `UpdatesEggVariables` return the updated model.
Mounts (`CreatesMounts`, `UpdatesMounts`, `DeletesMounts`, `AttachesEggsToMounts`,
`AttachesNodesToMounts`, `DetachesEggsFromMounts`, `DetachesNodesFromMounts`), tags
(`CreatesTags`, `UpdatesTags`, `DeletesTags`), API keys (`UpdatesApiKeys`, and `DeletesApiKeys` for
application and account keys alike), account SSH keys (`CreatesSshKeys`, `DeletesSshKeys`),
egg variable deletion (`DeletesEggVariables`) and the server install-status toggle
(`TogglesServerInstallStatus`) have contracts of their own as well.

Resolve the same contracts from the container to call core behaviour instead of
reimplementing it. An action that takes an input array validates it with the same rules
as the API, whoever calls it, and throws a `ValidationException` naming the field that
failed; values arrive as Laravel's rules accept them, so `"1"` is a boolean and `"25"` an
integer. Pass the field names the Admin API uses. Extension form field values for a model
go under `extensions` and are only accepted from a request that validated them.

An action that changes a server or an account writes the `server:*` or `user:*` activity
entry for that change itself, so it shows on the server's or user's activity feed whoever
calls it. A call from an extension, a console command or the Application API is logged the
same way as one from the client API, with the signed-in user, if there is one, as the
actor. The admin API also writes its own `admin:*` entry to the admin activity log. A
wrapper that does not call the inner action leaves no entry. Reading a file and downloading
a file or a backup are logged by the endpoint the user called, not by `ReadsFileContents`
or `GeneratesBackupDownloadLinks`.

Beside the mutating actions, these read or complete on your behalf:

| Contract | Call |
| --- | --- |
| `Contracts\Files\ReadsFileContents` | `read($server, $path, ?int $maxBytes = null): string`, bounded by the panel's editable file size unless a limit is given. |
| `Contracts\Files\ListsDirectories` | `list($server, $directory = '/')`: the directory's `DaemonFileObject` entries. |
| `Contracts\Servers\ReadsServerLogs` | `read($server, $lines = 100)`: recent console lines, oldest first, at most `MAX_LINES`. |
| `Contracts\Servers\ReadsServerState` | `read($server): ServerState`, live state and usage from the cache the client API shares (up to 20 seconds old). |
| `Contracts\Servers\ChangesServerEgg` | `change($server, $egg, keepVariables: false)`: resets startup, image and variables to the new egg, optionally carrying over values whose names and rules still match. |
| `Contracts\Servers\UpdatesServerDockerImage` | `update($server, $image)`: sets the Docker image alone, leaving the startup command and install settings as they are. The image is not checked against the egg. |
| `Contracts\Users\CompletesLogins` | `complete($user)` after you verified a first factor (it issues the two-factor checkpoint when the account needs one), `establish($user)` once every factor has passed, and `pendingCheckpoint()`. |

These actions do not check permissions; authorize the caller first, as the core
controllers do. They can be wrapped like any other action.

### Commands and scheduled tasks

`registerCommands([CleanLogsCommand::class])` adds artisan commands and
`registerSchedule(fn (Schedule $schedule) => $schedule->command('myext:clean-logs')->daily())`
defines tasks on the panel's scheduler. Both exist only while the extension is enabled and
booted successfully. A command's name and every alias must start with the extension id and
a colon (`myext:clean-logs`) and must not already exist; a command that breaks either rule
is left out and recorded against the extension. A scheduled task that fails, or a schedule
callback that throws, is recorded against the extension; a callback that throws schedules
nothing. As with Laravel's own commands, one that declares its name with `#[AsCommand]` is
only constructed when it runs; any other is constructed whenever artisan starts.

`php artisan about` lists every installed extension with its version and state under
"Extensions".

### Head tags

`registerHeadTags` adds `<meta>` and `<link>` elements to the panel's document head on
the server, for guests and signed-in users alike:

```php
$this->registerHeadTags(fn (): array => [
    ['tag' => 'link', 'rel' => 'manifest', 'href' => '/extensions/pwa/manifest.webmanifest'],
    ['tag' => 'meta', 'name' => 'theme-color', 'content' => $this->settings()->get('theme_color', '#0f172a')],
]);
```

Only `meta` (`name` or `property`, `content`, `media`) and `link` (`rel`, `href`, `sizes`,
`type`, `media`, `color`, `crossorigin`, `hreflang`, `title`) exist. `rel` is limited to
`manifest`, icon relations, `alternate`, `canonical`, `search`, `preconnect`, and
`dns-prefetch`; `href` must be an http(s) URL or a path on the panel. Scripts, stylesheets,
inline styles, event handlers, `http-equiv`, and the panel's own `csrf-token`, `viewport`,
`robots`, and `referrer` meta tags are rejected, and values are escaped. An element the
panel also ships (theme color, web app manifest, favicons) replaces the panel's. A static
array is validated when registered and an invalid one fails the extension's boot. A
closure runs when the layout renders and its result is cached for
`extensions.head_tags_cache_seconds` (60 by default); an invalid result is recorded and
contributes nothing. Up to 32 tags per extension. A registered `theme-color` is not
rewritten by the SPA; set the `--theme-color` token instead when the colour belongs to a theme.

### Root paths

Routes outside `/extensions/<id>` need a top-level prefix claimed in the manifest:

```json
{ "routes": { "root": ["go"] } }
```

`registerRootRoutes($this->extensionPath('routes', 'root.php'))` mounts the file under
every claimed prefix, or under one with a second argument; undeclared prefixes are
refused. Routes run in the `web` group, are throttled per client
(`extensions.root_routes_per_minute`, 120 by default), are public unless the file adds
auth middleware, and are named `extensions.<id>.root.<prefix>.<name>`. A prefix is a
lowercase slug of at most 48 characters, up to eight per extension. Prefixes the panel
uses (its API, SPA and asset paths, the first segment of any core route, anything in the
public directory) are rejected when the manifest is read, and enabling fails while another
enabled extension claims the same prefix. Extension routes, under `/extensions/<id>` or a
claimed prefix, are matched before the SPA's catch-all route.

## Styling

The panel and every extension compile their own Tailwind stylesheet, and all of them load
into the same `theme` and `utilities` cascade layers, each sorted on its own. Builds that
share a class name override each other: an extension's `.flex` beats the panel's
`.lg:hidden`, and one extension's `.grid-cols-2` beats another's `.sm:grid-cols-3`. Every
extension therefore builds Tailwind with a prefix of its own, declared in `extension.json`:

```json
"ui": { "entry": "dist/client.js", "prefix": "hw" }
```

`ui.prefix` is 2 to 12 lowercase letters (`^[a-z]{2,12}$`, the only characters Tailwind
accepts). Tailwind variant names (`sm`, `dark`, `hover`, `group`, ...), theme namespaces
(`color`, `text`, ...), the first segment of the panel's custom properties (`terminal`,
`layout`, `tw`, ...) and `panel`, `core`, `ptero` are reserved; `manifest.schema.json` lists
them all. Two enabled extensions cannot share a prefix: install, enable and upgrade fail with
`Extension "b" cannot use Tailwind prefix "hw": enabled extension "a" also declares it.`
`p:extension:make` derives a prefix from the id (`hello-world` gives `hw`) or takes `--prefix`.

`src/client/styles.css`, imported from the entry, uses the same prefix on both imports:

```css
@import 'tailwindcss/theme.css' layer(theme) prefix(hw);
@import 'tailwindcss/utilities.css' layer(utilities) prefix(hw);
@import '@pterodactyl/sdk/theme.css';
@source './**/*.{ts,tsx}';
```

Do not import `tailwindcss` itself or its preflight (the panel already resets the page), and
keep the SDK theme last. It maps the panel's tokens, so utilities resolve to the live values
of the active panel theme: `hw:bg-card` is `var(--card)`, `hw:w-sidebar` is
`var(--layout-sidebar-width)`, `hw:p-4` is `calc(var(--spacing) * 4)`. Only the semantic
colours exist (`hw:bg-card`, `hw:text-muted-foreground`); Tailwind's palette
(`hw:bg-red-500`) is not available.

The prefix is written once, first, as if it were a variant:

| Write | Not | |
| --- | --- | --- |
| `hw:flex` | `flex` | An unprefixed class is not in your stylesheet; it only works while the panel happens to emit it. |
| `hw:lg:hover:bg-accent` | `lg:hw:hover:bg-accent` | The prefix comes before every variant. |
| `hw:mt-4!` | `!hw:mt-4` | The important modifier stays at the end. |
| `hw:-mt-2` | `-hw:mt-2` | The minus stays on the utility. |
| `hw:group` with `hw:group-hover:underline` | `group` | `group` and `peer` markers are prefixed too. |
| `@apply hw:flex hw:hover:bg-accent;` | `@apply flex;` | `@apply` takes the same prefixed names. |

Hand-written classes are yours to name and belong outside the Tailwind layers. `@utility card`
defines `hw:card`, and a `@theme` variable `--color-brand` is emitted as `--hw-color-brand`
and used as `hw:bg-brand`.

Classes passed to SDK components and to a view's `Default` are merged with the component's
own for the prefix of every enabled extension: `<Button className="hw:mt-4">`
drops the button's `mt-*` default, per variant, exactly as a panel class would
(`hw:hover:bg-accent` replaces `hover:bg-primary/90`; `hw:bg-accent` alone does not). In tests
pass the prefix to the host: `createViewTestHost(name, { model, prefix: 'hw' })`.

`p:extension:doctor`, `p:extension:pack`, install and enable all reject a build whose CSS has
a rule in `@layer utilities` that is not built on a `.hw\:` class, or a custom property in
`@layer theme` that does not start with `--hw-`, for the prefix that extension declares. A
build with either layer and no `ui.prefix` is rejected with the line to add.

## Views

Every layout, page and block of the panel is a named view: an engine hook that produces its
model, and a stack of layers that render it. Classic, the stock UI, is the bottom layer; the
active theme and its parents stack above it, then extension overrides in the configured order.
Each layer receives `{ model, Default, ...inputs, children }`, and `Default` renders the layer
below. Themes and extensions write layers the same way; [Views](#views-1) in the Themes chapter
covers the API in full, and the [view catalogue](#view-catalogue) lists every view with its
model, inputs, slots and fixtures. The catalogue also ships as `@pterodactyl/sdk/views.json`.

### Overriding a view

Declare the views an extension overrides in `extension.json` under `ui.views`, with a
`requires.sdk` constraint such as `>=2.0.0-beta.5 <3.0`, and register each layer with
`views.override(name, layer)` during `setup`. A layer is a component, or `{ load }` with an
importer the panel calls when the view first renders. Overrides are not exclusive: several
enabled extensions and the active theme may override the same view, and each layer's `Default`
renders the one below. `p:extension:doctor` reports a name under a core namespace that the
panel does not have.

```json
{ "ui": { "entry": "dist/client.js", "prefix": "hw", "views": ["dashboard.serverCard", "server.files.details"] } }
```

<!-- sample: snippets/extension.tsx#override -->
```tsx
/** A layer over the server card: it keeps the layers below and adds a note. */
function ServerCard({ Default, model }: ViewProps<'dashboard.serverCard'>) {
    return (
        <>
            <Default className='hw:gap-3' />
            {model.state.kind === 'unavailable' && <p className='hw:text-xs'>Ask support about {model.name}.</p>}
        </>
    );
}
```
<!-- /sample -->

<!-- sample: snippets/extension.tsx#setup -->
```tsx
views.override('dashboard.serverCard', ServerCard);
views.override('server.files.details', { load: () => import('./FileDetails') });
views.define<BalanceModel>('billing.balance', {
    context: 'app',
    use: useBalance,
    Default: Balance,
    fixtures: {
        funded: () => ({ credits: 120, topUp: () => {} }),
        empty: () => ({ credits: 0, topUp: () => {} }),
    },
});
// Wherever classic has no place for it yet, a slot places the view.
slots.register('dashboard.after', () => <View name='billing.balance' />);
```
<!-- /sample -->

`packages/hello-world` overrides the server card, the file details and the file editor, each with
a test. Classes passed to `Default` merge with the layer below's under the extension's prefix (see
[Styling](#styling)).

The server card and file details render inside core links. Keep interactive controls in action
slots. The panel retains queries, permissions, navigation, accessible link names, selection, and
menus. The classic server-card identity part includes the `dashboard.serverRow.name.after` slot.

### Defining a view

`views.define(name, { context, use, Default, fixtures, placeholder })` adds a view of the
extension's own, so themes and other extensions can restyle or place it:

| Option | |
| --- | --- |
| `context` | Where it can render: `app` (every signed-in page), `server`, `account`, `admin` or `auth`. |
| `use(inputs)` | The hook that produces the model. It is the view's only data source. |
| `Default` | The bottom layer, typed by `ExtensionViewProps<Model>`. Build it from the [UI kit](#ui-kit) and it takes the active theme's skins. |
| `fixtures` | Models by name, which the workbench and tests render without a backend. The first is the default. |
| `placeholder` | Shown while a layer above it loads. |

Use a namespace of your own, such as your extension id: `layout.` and `page.` names are refused,
and so is a second definition of a name. The view renders wherever a layer or a slot places it
with `<View name="billing.balance" />`; a theme that may run without the extension places it
with `optional`, which renders nothing while no installed package defines it.

<!-- sample: snippets/extension.tsx#define -->
```tsx
interface BalanceModel {
    readonly credits: number;
    readonly topUp: () => void;
}

function useBalance(): BalanceModel {
    return { credits: 120, topUp: () => window.open('/billing', '_self') };
}

/** The bottom layer of the extension's own view; themes restyle or move it. Built from the UI kit, it takes their skins. */
function Balance({ model, className }: ExtensionViewProps<BalanceModel>) {
    return (
        <Card title='Balance' className={className} actions={<Badge tone='success'>{model.credits} credits</Badge>}>
            <Button size='xsmall' onClick={model.topUp}>
                Top up
            </Button>
        </Card>
    );
}
```
<!-- /sample -->

### Slots inside views

A view renders the slots listed for it in the catalogue, and a layer that replaces it has to
render them too, or the extensions mounted there disappear. Rendering `Default` renders the
slots of the layers below. In development, a layer that renders none of its view's slots logs
a warning naming them after three seconds. The before and after slots of a page are rendered by
the engine around the page view, so no layer can drop them.

### File editor

`server.files.editor` replaces the editing surface of `/server/$id/files/edit` and `/files/new`. The panel keeps the route, the new-file name dialog, the write mutation, permission checks and the unsaved-changes guard, whatever the theme draws. `page.server.files.editor` places the breadcrumbs, loading and error screens around it. The layer mounts once per opened document, after its content has loaded.

| Model field | |
| --- | --- |
| `path`, `name`, `isNew` | The open file. For a new file `path` is its directory and `name` is empty. |
| `content` | Text to open: the loaded file, or the restored draft of a new file. It does not change while the document is open. |
| `Editor` | The panel's code editor, bound to this document and themed with the `--editor-*` tokens. |
| `language`, `languages`, `setLanguage(mime)` | Syntax hint as a MIME type (`text/x-yaml`), the ones the editor knows, and switching. |
| `readOnly` | The user lacks `file.update` (or `file.create` for a new file). |
| `dirty`, `saving` | Derived by the panel from the reported buffer and the running write. |
| `change(content)`, `read()` | Report the buffer after every edit, and read the last one reported. The panel saves exactly this text. |
| `save()` | Write the buffer in place; a new file first asks for its name in the panel's dialog. Resolves `false` when read-only, cancelled or failed. |
| `saveAs(name)` | Write the buffer to a name beside the file, or an absolute path, and open it. Needs `file.create`. |

```tsx
import { useExtensionAction, type ViewPartProps, type ViewProps } from '@pterodactyl/sdk';

function Buffer({ model }: ViewPartProps<'server.files.editor'>) {
    const save = useExtensionAction('save', async () => void (await model.save()));
    return (
        <textarea
            defaultValue={model.content}
            readOnly={model.readOnly}
            onChange={(event) => model.change(event.target.value)}
            onKeyDown={(event) => event.ctrlKey && event.key === 's' && (event.preventDefault(), save())}
        />
    );
}
export default function Editor({ Default }: ViewProps<'server.files.editor'>) {
    return <Default parts={{ editor: Buffer }} />;
}
```

Because the panel holds the buffer, a replaced `editor` part works with the classic Save button, and the classic editor continues with the user's text if the layer fails to render. Do not add your own `useNavigationBlocker` here.

### File browser

`server.files.manager` replaces the browser on `/server/$id/files`: toolbar, listing and selection bar. The panel keeps the page, the directory query, the selection store, uploads, and every mutation and navigation. `server.files.before` and `server.files.after` stay around the view; the `server.files.toolbar`, `rowActions` and `selectionActions` slots and the `server.files.details` view belong to the classic parts and render only where you keep those parts.

`model.entries` are `FileManagerEntry` rows of `model.directory` (directories first, at most 250, `truncated` when there are more) with `name`, `path`, `kind`, `size`, `mimetype`, `mode`, `modeBits`, `modifiedAt`, `openable`, `extractable`, the `destination` opening it links to, and the API's `file`. `loading` is true until a directory is first listed, `refreshing` during later fetches. `selection` holds the selected names and `permissions` says which of `create`, `update`, `delete` and `archive` the user has. `breadcrumbs` lead from `container` to the directory, `newFileDestination` opens the editor for a new file, `uploads` lists the uploads in progress, `resolve(name)` shows where a relative name lands, and `slot` is what the file slots receive.

`model.actions` take names in the current directory: `open(name)` enters a directory or opens a text file in the editor, `navigate(directory)`, `newFile()`, `select(names)`, `refresh()`, `createDirectory(name)`, `rename([{ from, to }])` (a relative path in `to` moves), `move(names, directory)`, `remove(names)`, `copy(name)`, `archive(names)`, `extract(name)`, `chmod([{ file, mode }])`, `download(name)`, `upload(files)`, `cancelUpload(id)` and `clearUploads()`. They run the panel's own mutations, with its cache updates and notifications; `remove` and `archive` drop their names from the selection, `rename`, `move` and `chmod` clear it. A call the user has no permission for rejects before any request; the API enforces the same rule. `remove` deletes at once, so confirm first. For files outside the current directory, such as a tree view, use `serverFilesQueryOptions` and the mutation hooks.

```tsx
export default function Browser({ model, parts }: ViewProps<'server.files.manager'>) {
    const open = useExtensionAction('open', (name: string) => model.actions.open(name));
    return (
        <>
            <parts.toolbar model={model} />
            <ul aria-busy={model.loading}>
                {model.entries.map((entry) => (
                    <li key={entry.path}>
                        <button disabled={!entry.openable} onClick={() => open(entry.name)}>{entry.name}</button>
                    </li>
                ))}
            </ul>
        </>
    );
}
```

### Loading and failure

While a bundle that declares a view loads, the view shows its placeholder; mounts of one page share that decision. After five seconds, or when the import or the layer fails, the layers below render instead, and a late bundle cannot replace a view already shown. A layer that throws while rendering is reported against its package and replaced by the layer below. Core state remains outside the layer. Use local Suspense for extension-owned asynchronous rendering and `useExtensionAction` for callbacks.

The testing entry exports `createViewTestHost(name, { model?, fixture?, input?, extensionId?, prefix? })`, which resolves to a `Wrapper`, the typed `props` of a layer above classic, and `dispose`, plus the model fixtures `createTestServerCard`, `createTestFileDetails`, `createTestFileEditor`, `createTestFileManager` and `createTestFileManagerEntry`. It exercises classic's layers and parts without issuing core queries; nested views read their fixtures. Pass `vi.fn()` callbacks in the model to assert on `save`, `change` or an action, and call `dispose()` after unmounting.

## UI kit

The SDK's components are the panel's design system: `Button`, `Input`, `Select`, `Card`,
`ListRow`, `Badge`, `Dialog`, `Table`, `Empty` and the rest of the [primitives](#primitives).
Each one takes its classes from the active theme's skin and marks its parts with `data-ui`,
`data-part` and its state (`data-color="red"`). An extension built from them looks native under
every theme, and a theme restyles it without knowing it exists. The workbench's UI kit shows each
primitive in every variant under the active theme.

A `className` passed to a primitive is appended to its skin's classes, and a conflicting utility
replaces the skin's: `<Button className="hw:mt-4">` drops the button's own `mt-*` class.

## Themes

A theme is an extension with `"type": "theme"`. It decides how every page of the panel looks
while the panel keeps the data, permissions, routing, mutations, realtime updates and
accessibility frame. Only one theme is active at a time.

### How themes work

A theme works through four parts, and nothing else:

| Part | What it is | Where it lives |
| --- | --- | --- |
| Stylesheet and tokens | The panel's own Tailwind stylesheet, rebuilt with the theme's classes and tokens. It replaces the panel's stylesheet. | `src/theme.css`, built to `dist/theme.css` |
| UI kit skins | Class recipes for the design-system primitives (`Button`, `Card`, `Dialog`, ...), which extensions use too. | `defineTheme({ ui })` |
| Views | Components for named layouts, pages and blocks, each given an engine model. | `defineTheme({ views })` |
| Slots | The places extensions mount content. Views render them. | `<Slot name="…" />` |

The stock UI, **classic**, is itself a theme written on this API: its views import only
`@pterodactyl/sdk` and `@pterodactyl/sdk/theme`. Anything classic draws, a theme can draw
differently, and `pterodactyl-theme eject` copies classic's source for a view as a starting point.

Views and skins form a **layer chain**: classic at the bottom, then the theme's parents, then the
theme, then extension overrides. A layer receives `Default`, which renders the layer below, so a
theme can wrap classic, hand it a changed model, swap one of its parts, or replace it. A layer that
throws is reported and the layer below renders in its place, so a broken view never takes the page
down. A theme may override two views or all of them; whatever it leaves alone stays classic.

A theme can do everything an extension can (a provider, screens, slots, PHP settings), but it
cannot declare `ui.prefix` or `ui.views`: it builds the panel stylesheet itself and overrides views
through `defineTheme`.

### Theme quick start

```sh
php artisan p:extension:make aurora --theme
cd extensions/aurora
npm install
php ../../artisan p:extension:dev . --enable     # build, install and enable
php ../../artisan p:theme:activate aurora
php ../../artisan p:extension:dev . --watch      # rebuild and republish on every change
```

`p:extension:make --theme` writes a working theme into the extensions directory (`--out` picks
another): the manifest with four settings, a stylesheet with a dark and a light palette, a
`Button` and a `Card` skin, a layer over the app layout, a test and a README. Its `package.json` has
`build`, `dev`, `typecheck`, `test`, `doctor`, `types` and `pack` scripts; the last three run the
panel's artisan commands on the package. `p:extension:dev` runs `npm run build`, installs the
package and enables it. With `--watch` it runs `npm run dev` and republishes each build; on a panel
with `APP_DEBUG=true`, a change that only touches the stylesheet swaps it into open pages without a
reload, and any other change reloads them.

Instead of activating, an administrator can choose **Preview for me** on **Admin > Themes** to use
the theme in their own session only. `/panel/theme-workbench` renders every view and primitive
from fixtures under the active or previewed theme.

The [Aurora sample](#recipes) used throughout this chapter lives in `docs-samples/aurora` of this
package. The SDK's own checks compile it, run its tests and build it.

### The theme manifest

<!-- sample: aurora/extension.json -->
```json
{
    "$schema": "./node_modules/@pterodactyl/sdk/manifest.schema.json",
    "id": "aurora",
    "type": "theme",
    "name": "Aurora",
    "version": "1.0.0",
    "description": "A glass sidebar theme with a light and a dark scheme.",
    "icon": "palette",
    "requires": { "panel": "^2.0.0-dev", "sdk": ">=2.0.0-beta.5 <3.0" },
    "ui": { "entry": "dist/client.js" },
    "theme": {
        "extends": "classic",
        "stylesheet": "dist/theme.css",
        "schemes": ["dark", "light"],
        "settings": {
            "accent": { "type": "color", "label": "Accent", "default": "#8b5cf6", "token": "--aurora-accent" },
            "sidebar": {
                "type": "select",
                "label": "Sidebar",
                "options": { "left": "Left", "right": "Right" },
                "default": "left",
                "attribute": "data-aurora-sidebar"
            },
            "density": {
                "type": "select",
                "label": "Density",
                "options": { "comfortable": "Comfortable", "compact": "Compact" },
                "default": "comfortable",
                "attribute": "data-aurora-density"
            },
            "blur": {
                "type": "number",
                "label": "Glass blur",
                "default": 16,
                "min": 0,
                "max": 40,
                "step": 4,
                "unit": "px",
                "token": "--aurora-blur"
            },
            "logo": {
                "type": "image",
                "label": "Logo",
                "help": "Shown in the sidebar and on the sign-in page.",
                "token": "--brand-logo"
            },
            "tagline": {
                "type": "text",
                "label": "Sign-in tagline",
                "default": "Every server, one place.",
                "maxLength": 80
            },
            "liveStats": { "type": "toggle", "label": "Live stats on the dashboard", "default": true }
        }
    }
}
```
<!-- /sample -->

| Key | |
| --- | --- |
| `type` | `"theme"`. |
| `requires.panel`, `requires.sdk` | Composer-style constraints, checked on install, enable and upgrade. |
| `ui.entry` | `dist/client.js`, the built `src/theme.tsx`. Optional: a theme of tokens and CSS alone needs no entry. |
| `theme.extends` | The id of an installed theme to build on; `classic` when left out. See [Extending another theme](#extending-another-theme). |
| `theme.stylesheet` | Always `dist/theme.css`, built by `defineThemeConfig` beside `dist/theme.json`. Leave it out for a theme without a stylesheet. |
| `theme.schemes` | The colour schemes the theme supports, `dark` and/or `light`; the first is the default. When left out, the parent's apply; classic offers `dark` only. |
| `theme.settings` | Up to 64 settings administrators change on the Themes page. |

#### Settings

Each setting has a `type`, a `label`, an optional `help` and `default`, and writes its value to a
CSS custom property (`token`), an `<html>` data attribute (`attribute`), both, or neither. The
server writes tokens into `<style id="theme-settings">` as `:root:root { … }`, which outranks the
stylesheet's own `:root` and `.dark` rules, and attributes onto `<html>`, before any CSS loads, so
nothing flashes. Values are validated by type when saved, and a stored value that no longer fits
the declaration (an option you removed, a number outside new limits) reads as the default.

| Type | Options | Value | Token | Attribute |
| --- | --- | --- | --- | --- |
| `color` | | `#rgb`, `#rgba`, `#rrggbb`, `#rrggbbaa` or `oklch(L C H [/ A])` | The colour, lower case | The colour |
| `select` | `options`: value to label; values are CSS identifiers | One of the option values | The value, as an identifier | The value |
| `toggle` | | `true` or `false` | `1` or `0` | `true` or `false` |
| `number` | `min`, `max`, `step`, `unit` (`%` or up to 4 letters) | A number within the limits, on a step | The number and unit: `0.75rem` | The number |
| `text` | `maxLength`, 255 unless set (1 to 1000) | A string | A quoted, escaped CSS string | The text |
| `image` | | An upload on the Themes page: PNG, JPEG, GIF, WebP, AVIF or plain SVG, up to 1 MB. No default. | `url("…")` | The URL |

A token is a custom property name such as `--aurora-accent`; an attribute is a `data-*` name other
than `data-theme`, which the panel writes itself. Two settings cannot write the same token or
attribute, and setting names match `^[a-zA-Z][a-zA-Z0-9_]{0,47}$`. A default has to pass the same
checks as a saved value.

The Themes page builds a form from the settings and previews unsaved values on the page by setting
the tokens and attributes as you edit. Code reads the values the page loaded with through
`useThemeSettings()`. For typed values, run `php artisan p:extension:types <path>` (the scaffold's
`npm run types`), which writes `src/theme-types.ts`:

<!-- sample: aurora/src/theme-types.ts -->
```ts
// Generated by p:extension:types. Do not edit.
import type { ExtensionConfig as SdkExtensionConfig, ExtensionConfigValue } from '@pterodactyl/sdk';

export type ExtensionId = 'aurora';
export type ExtensionScreenId = never;
export type ExtensionPermission = never;
export interface ExtensionConfig extends SdkExtensionConfig {}
// Visitors who are not signed in receive only these keys; every other key is absent for them.
export type ExtensionPublicConfigKey = never;
export type ExtensionPublicConfig = Pick<ExtensionConfig, ExtensionPublicConfigKey>;
// The theme's settings, as useThemeSettings<ThemeSettings>() returns them.
export interface ThemeSettings {
    readonly accent: string;
    readonly sidebar: 'left' | 'right';
    readonly density: 'comfortable' | 'compact';
    readonly blur: number;
    readonly logo: string | null;
    readonly tagline: string;
    readonly liveStats: boolean;
}
```
<!-- /sample -->

```tsx
const { tagline } = useThemeSettings<ThemeSettings>();
```

Some settings are best read by CSS alone. An image setting writing `--brand-logo` becomes the
`brand.logoUrl` of the layout models. An attribute switches layouts without JavaScript: classic's
account and server navigation moves into a sidebar on wide screens when `<html>` has
`data-sub-navigation="side"`, so a `select` setting with that attribute and the options `side` and
`top` gives administrators the choice.

### The stylesheet

`src/theme.css` starts with one import, then holds the theme's own CSS in plain Tailwind:

```css
@import '@pterodactyl/sdk/theme-entry.css';
```

`theme-entry.css` is the panel's stylesheet (`panel.css`: Tailwind, the tokens, base styles, the
custom variants) together with `core-classes.txt`, every class the panel's markup uses, generated
with each panel build. Tailwind adds the classes your own files use. The result, `dist/theme.css`,
replaces the panel's stylesheet, so a theme can retune the design system rather than override it:

- Set tokens for each scheme in `.dark { … }` and `.light { … }`, and scheme-independent ones in
  `:root`. Your rules come after the panel's, so they win.
- Redefine theme variables in `@theme`: `--font-sans`, `--font-header`, shadows, breakpoints.
- Add `@custom-variant`, `@utility` and `@plugin` like in any Tailwind project.
- Write classes without a prefix: `bg-card`, `lg:hidden`. Extensions keep their own prefixes.

The panel's colour utilities are its roles (`bg-card`, `text-muted-foreground`): Tailwind's default
palette is removed with `--color-*: initial`. Add colours of your own in `@theme`, such as
`--color-brand: oklch(0.7 0.2 300);` for `bg-brand`. The `dark:` variant applies under
`<html class="dark">`; declare `@custom-variant light (&:is(.light *));` if you want a `light:` one.

<!-- sample: aurora/src/theme.css#tokens -->
```css
/* Both schemes. The "accent" setting writes --aurora-accent; the colour roles derive from it. */
:root {
    --radius: 0.875rem;
    --layout-sidebar-width: 15rem;
    --primary: var(--aurora-accent, #8b5cf6);
    --primary-foreground: oklch(0.985 0 0);
    --ring: color-mix(in oklab, var(--primary) 60%, transparent);
    --accent: color-mix(in oklab, var(--primary) 65%, white);
    --accent-foreground: oklch(0.2 0.02 280);
    --selection: color-mix(in oklab, var(--primary) 35%, transparent);
    --glass: color-mix(in oklab, var(--card) 60%, transparent);
    --glass-border: color-mix(in oklab, var(--foreground) 12%, transparent);
}

.dark {
    --background: oklch(0.17 0.02 280);
    --foreground: oklch(0.94 0.01 280);
    --card: oklch(0.23 0.025 280);
    --card-foreground: var(--foreground);
    --popover: oklch(0.27 0.03 280);
    --popover-foreground: var(--foreground);
    --muted: oklch(0.25 0.02 280);
    --muted-foreground: oklch(0.7 0.02 280);
    --secondary: oklch(0.3 0.03 280);
    --border: oklch(0.32 0.03 280);
    --terminal-background: oklch(0.13 0.02 280);
}

.light {
    --background: oklch(0.97 0.01 280);
    --foreground: oklch(0.22 0.03 280);
    --card: oklch(1 0 0);
    --card-foreground: var(--foreground);
    --popover: oklch(0.99 0.005 280);
    --popover-foreground: var(--foreground);
    --muted: oklch(0.94 0.01 280);
    --muted-foreground: oklch(0.5 0.02 280);
    --secondary: oklch(0.92 0.015 280);
    --border: oklch(0.88 0.015 280);
    --terminal-background: oklch(0.2 0.02 280);
    --terminal-foreground: oklch(0.92 0.01 280);
}
```
<!-- /sample -->

**Fonts and images** ship with the theme. Reference them relative to the stylesheet and the build
copies them beside `dist/theme.css`:

<!-- sample: aurora/src/theme.css#font -->
```css
/* Bundled with the theme: the build copies the file beside dist/theme.css. */
@font-face {
    font-family: 'Aurora Sans';
    font-style: normal;
    font-weight: 100 900;
    font-display: swap;
    src: url('./fonts/aurora-sans.woff2') format('woff2');
}

@theme {
    --font-sans: 'Aurora Sans', system-ui, sans-serif;
    --font-header: 'Aurora Sans', system-ui, sans-serif;
}
```
<!-- /sample -->

The build warns that `/assets/fonts/ibm-plex-sans-latin-wght-normal.woff2` did not resolve: that is
the panel's own font, served by the panel, and the warning is expected.

**Rules.** `p:extension:doctor`, `p:extension:pack`, install and enable refuse a theme build that
loads anything from another origin: `@import url(https://…)`, a font on a CDN, an `http:` or `//`
URL anywhere in its CSS. Data URLs are allowed. They also refuse a `dist/theme.css` over 250 KB, a
`dist/client.js` over 200 KB (chunks loaded with `lazyView` do not count), and a `dist/theme.json`
naming a core view that does not exist. The build itself fails when theme JavaScript imports CSS:
the panel loads only `dist/theme.css`.

**Stylesheet fallback.** `dist/theme.json` records the hash of the `core-classes.txt` the
stylesheet was built with. When the panel's differs, because the panel was updated after the theme
was built, the panel links its own stylesheet and then the theme's over it, so classes the panel
added since still exist. `p:extension:doctor` and `p:extension:pack` warn about it; rebuild the theme
with the panel's SDK to make its stylesheet the only one again.

#### Tokens

Every custom property of the panel stylesheet. The roles are what classic uses each one for; the
utilities column names the Tailwind classes that read it, such as `bg-card` and `text-card` for
`*-card`. The full declarations, with classic's values for both schemes, are in
`@pterodactyl/sdk/panel.css` and `@pterodactyl/sdk/theme-tokens.css`.

<!-- generated:tokens -->
#### Surfaces and text

| Token | Role | Utilities |
| --- | --- | --- |
| `--background` | Page background. | `*-background` |
| `--foreground` | Body text. | `*-foreground` |
| `--card` | Cards, rows and raised panels. | `*-card` |
| `--card-foreground` | Text on cards. | `*-card-foreground` |
| `--popover` | Menus, tooltips, hover fills and the panel navigation bar. | `*-popover` |
| `--popover-foreground` | Text on popovers. | `*-popover-foreground` |
| `--muted` | Quiet fills: table heads, disabled inputs. | `*-muted` |
| `--muted-foreground` | Secondary text, icons and placeholders. | `*-muted-foreground` |
| `--sunken` | A recessed surface on a card: meter tracks, code chips. | `*-sunken` |
| `--border` | Borders and dividers. | `*-border` |
| `--input` | Input fills and borders. | `*-input` |
| `--ring` | Focus rings and outlines. | `*-ring` |

#### Actions and states

| Token | Role | Utilities |
| --- | --- | --- |
| `--primary` | Primary buttons, links in focus, selected items. | `*-primary` |
| `--primary-foreground` | Text on primary. | `*-primary-foreground` |
| `--secondary` | Secondary fills and hover states. | `*-secondary` |
| `--secondary-foreground` | Text on secondary. | `*-secondary-foreground` |
| `--accent` | Highlights: the active navigation underline, info alerts, the page progress bar. | `*-accent` |
| `--accent-foreground` | Text on accent. | `*-accent-foreground` |
| `--destructive` | Danger buttons, errors, offline status. | `*-destructive` |
| `--destructive-foreground` | Text on destructive. | `*-destructive-foreground` |
| `--success` | Success states and running servers. | `*-success` |
| `--success-foreground` | Text on success. | `*-success-foreground` |
| `--warning` | Warnings and starting servers. | `*-warning` |
| `--warning-foreground` | Text on warning. | `*-warning-foreground` |
| `--caution` | The orange button colour. | `*-caution` |
| `--caution-foreground` | Text on caution. | `*-caution-foreground` |
| `--state-foreground` | The text colour the success and warning foregrounds default to. |  |
| `--chart-*` | Five chart series colours (`--chart-1` to `--chart-5`). |  |

#### Sidebar

| Token | Role | Utilities |
| --- | --- | --- |
| `--sidebar` | Sidebar background; defaults to `--card`. | `*-sidebar` |
| `--sidebar-foreground` | Sidebar text. | `*-sidebar-foreground` |
| `--sidebar-primary` | The active sidebar item. | `*-sidebar-primary` |
| `--sidebar-primary-foreground` | Text on the active sidebar item. | `*-sidebar-primary-foreground` |
| `--sidebar-accent` | Sidebar hover fill. | `*-sidebar-accent` |
| `--sidebar-accent-foreground` | Text on the sidebar hover fill. | `*-sidebar-accent-foreground` |
| `--sidebar-border` | Sidebar borders. | `*-sidebar-border` |
| `--sidebar-ring` | Sidebar focus rings. | `*-sidebar-ring` |

#### Shape, type and layout

| Token | Role | Utilities |
| --- | --- | --- |
| `--radius` | The radius scale `rounded-sm` to `rounded-4xl` is computed from. |  |
| `--spacing` | Tailwind's spacing unit, which every `p-*`, `m-*`, `gap-*` and `w-*` multiplies: density. |  |
| `--font-sans` | Body typeface (`font-sans`). |  |
| `--font-header` | Heading typeface (`font-header`). |  |
| `--layout-content-width` | Width of page content (`max-w-panel`). | `*-panel` |
| `--layout-auth-width` | Width of the sign-in card (`max-w-auth`). | `*-auth` |
| `--layout-sidebar-width` | Width of sidebars (`w-sidebar`). | `*-sidebar` |
| `--spinner-overlay-opacity` | Opacity of the background behind a loading overlay. |  |

#### Browser chrome

| Token | Role | Utilities |
| --- | --- | --- |
| `--color-scheme` | The `color-scheme` of native widgets: `light` or `dark`. |  |
| `--theme-color` | The `<meta name="theme-color">` the panel keeps in sync. |  |
| `--selection` | Text selection background; unset keeps the browser default. |  |
| `--selection-foreground` | Text selection colour. |  |
| `--scrollbar-thumb` | Scrollbar thumb on hover, and `scrollbar-color`. | `*-scrollbar-thumb` |
| `--scrollbar-thumb-fill` | Scrollbar thumb at rest. | `*-scrollbar-thumb-fill` |
| `--scrollbar-track` | Scrollbar track. |  |
| `--brand-logo` | A `url(…)` the layouts show as the logo, usually written by an image setting. |  |

#### Console

| Token | Role | Utilities |
| --- | --- | --- |
| `--terminal-background` | Console background (`bg-terminal`). | `*-terminal` |
| `--terminal-foreground` | Uncoloured console output. | `*-terminal-foreground` |
| `--terminal-cursor` | Console cursor; matches the background to hide it. |  |
| `--terminal-selection` | Selected console text background. |  |
| `--terminal-selection-foreground` | Selected console text. |  |
| `--terminal-ansi-*` | The 16 ANSI colours: `black`, `red`, `green`, `yellow`, `blue`, `magenta`, `cyan`, `white` and their `bright-` forms. |  |

#### File editor

| Token | Role | Utilities |
| --- | --- | --- |
| `--editor-background` | Editor background. |  |
| `--editor-foreground` | Editor text. |  |
| `--editor-caret` | The caret. |  |
| `--editor-selection` | Selected text background. |  |
| `--editor-selected-foreground` | Text in selected search matches and completions. |  |
| `--editor-border` | Editor borders and gutters. |  |
| `--editor-muted` | Line numbers and other quiet text. |  |
| `--editor-active-line` | The line with the caret. |  |
| `--editor-search-match` | Search matches. |  |
| `--editor-search-match-selected` | The current search match. |  |
| `--editor-tooltip` | Tooltip and autocomplete background. |  |
| `--editor-tooltip-border` | Tooltip and autocomplete border. |  |
| `--editor-panel-shadow` | Shadow of the search panel. |  |
| `--editor-dialog-shadow` | Shadow of editor dialogs. |  |
| `--editor-syntax-*` | Syntax colours: `keyword`, `name`, `function`, `constant`, `definition`, `type`, `operator`, `string`, `invalid`. |  |
<!-- /generated:tokens -->

The terminal draws on a canvas and the editor renders its own DOM, so both read their tokens
directly and re-read them whenever a stylesheet or an attribute of `<html>` changes.

### UI kit skins

`defineTheme({ ui })` maps primitive names to skins. A skin maps the primitive's parts to recipes;
the parts, the state each recipe reads and the attributes they render are listed under
[Primitives](#primitives). `npx pterodactyl-theme skins Button` prints classic's skin of a
primitive to start from.

A recipe is one of:

- a string of classes;
- a [class-variance-authority](https://cva.style) recipe, `{ base, variants, compoundVariants, defaultVariants }`,
  whose variant keys are the primitive's state, with `true` and `false` for boolean state;
- a function of the state, returning classes.

<!-- sample: snippets/skins.tsx#recipe -->
```tsx
/** A class-variance-authority recipe: variant keys are the primitive's state, `true`/`false` for booleans. */
const badge = skin('Badge', {
    root: {
        base: 'inline-flex items-center gap-1 rounded-full font-medium',
        variants: {
            size: { sm: 'px-2 text-[0.6875rem]', md: 'px-2.5 py-0.5 text-xs' },
            variant: { solid: 'text-primary-foreground', soft: 'bg-transparent ring-1 ring-inset' },
        },
        compoundVariants: [
            { variant: 'solid', tone: ['success', 'info'], class: 'bg-success' },
            { variant: 'solid', tone: ['danger', 'warning'], class: 'bg-destructive' },
            { variant: 'solid', tone: ['neutral', 'accent'], class: 'bg-primary' },
            { variant: 'soft', tone: 'danger', class: 'text-destructive ring-destructive/40' },
        ],
        defaultVariants: { size: 'md' },
    },
});
```
<!-- /sample -->

<!-- sample: aurora/src/skins.ts#button -->
```ts
type ButtonState = PrimitiveState<'Button'>;

const fills: Record<ButtonState['color'], string> = {
    primary: 'bg-primary text-primary-foreground hover:not-disabled:bg-primary/85',
    grey: 'glass text-foreground hover:not-disabled:bg-popover',
    green: 'bg-success text-success-foreground hover:not-disabled:bg-success/85',
    red: 'bg-destructive text-destructive-foreground hover:not-disabled:bg-destructive/85',
    orange: 'bg-caution text-caution-foreground hover:not-disabled:bg-caution/85',
};
const sizes: Record<ButtonState['size'], string> = {
    xsmall: 'h-7 px-3 text-xs',
    small: 'h-9 px-4 text-sm',
    large: 'h-11 px-6 text-sm',
    xlarge: 'h-11 w-full px-6 text-sm',
};

/** A pill button. Only `root` is replaced; `spinner` and `label` keep classic's recipes. */
const button = skin('Button', {
    root: ({ color, size, secondary }) =>
        [
            'relative inline-flex items-center justify-center gap-2 rounded-full font-medium transition disabled:opacity-50',
            sizes[size],
            secondary
                ? 'bg-transparent text-foreground ring-1 ring-border hover:not-disabled:bg-popover'
                : fills[color],
        ].join(' '),
});
```
<!-- /sample -->

**Layers.** Skins stack like views: classic, then the theme's parents, then the theme. For each
part the topmost recipe wins and replaces the recipe below it whole; parts a skin leaves out keep
the recipe below. `skin(name, (below) => skin)` builds on the layers below instead: `below.root(state)`
returns their classes for a part. Classes are not merged inside a recipe, so add classes the lower
recipe does not set, or replace the part. A function given without `skin()` is read as a
replacement component, not as an extending skin.

<!-- sample: aurora/src/skins.ts#extend -->
```ts
/** Builds on the recipes below this layer by adding classes they do not set. */
const dialog = skin('Dialog', (below) => ({
    backdrop: (state) => `${below.backdrop(state)} backdrop-blur-sm`,
    description: (state) => `${below.description(state)} text-balance`,
}));
```
<!-- /sample -->

**Call sites.** A `className` passed where a primitive is used is appended after the recipe and
wins any conflict: `<Button className="mt-4">` replaces the recipe's own `mt-*` class.

**Attributes.** Every part renders `data-ui` with the primitive's name in kebab case and `data-part`
with the part's name. The root renders a `data-*` attribute for each state value: a boolean as
present or absent (`data-loading`), any other value as its text (`data-color="red"`). A few parts
carry state of their own, such as the `icon` part of a `Dialog`. Style with them in CSS when a
recipe is not enough: `[data-ui='button'][data-color='red'] { … }`. Primitives built on Base UI
also render its state, such as `data-checked`, `data-highlighted`, `data-popup-open` and
`data-starting-style`, which Tailwind reads as variants (`data-checked:bg-primary`).

**Replacing a primitive.** A presentational primitive (Replaceable in the table) can be replaced by
a component that receives the same props, typed by `PrimitiveProps<'Name'>`. It must not render the
primitive it replaces, and it should keep the attributes and accessibility of the original. The
other primitives keep their behaviour in core and can only be skinned; a component given for one is
a type error, and ignored with a warning at runtime. Replacing `Button` leaves `LinkButton` and
`RouterLinkButton`, which render links styled by the `Button` skin, as they are.

<!-- sample: snippets/skins.tsx#component -->
```tsx
/** A presentational primitive can be replaced by a component that takes the same props. */
function Banner({ type, title, className, children }: PrimitiveProps<'Alert'>) {
    return (
        <div role='alert' data-ui='alert' data-type={type} className={`rounded-xl border-l-4 p-4 ${className ?? ''}`}>
            {title && <p className='font-semibold'>{title}</p>}
            {children}
        </div>
    );
}
```
<!-- /sample -->

**Skins in your own components.** `useSkin(name, state)` binds a primitive's active skin, so an
element of your own can look like one and carry its attributes. Call the result with a part, extra
classes and part state to get props to spread, or use `.classes(part, …)` for the class string.

<!-- sample: snippets/skins.tsx#use-skin -->
```tsx
/** A theme's own element styled with the active skin of a primitive, attributes included. */
function ButtonLikeLink({ href, children }: { href: string; children: ReactNode }) {
    const ui = useSkin('Button', { color: 'primary', size: 'small', secondary: true, loading: false });

    return (
        <a href={href} {...ui('root', 'no-underline')}>
            {children}
        </a>
    );
}
```
<!-- /sample -->

In development, skinning a name that is not a primitive, or a part a primitive does not have, logs
a warning. A recipe that throws is logged and renders no classes.

#### Primitives

<!-- generated:primitives -->
| Primitive | Parts | State on `root` | Replaceable | Notes |
| --- | --- | --- | --- | --- |
| Alert | `root`, `icon`, `body`, `title`, `content` | `data-type`: success, info, warning, danger | yes |  |
| Avatar | `root` | `data-variant`: beam, marble, pixel, sunset, ring, bauhaus | yes |  |
| Badge | `root` | `data-tone`: neutral, success, warning, danger, info, accent; `data-size`: sm, md; `data-variant`: solid, soft | yes |  |
| Button | `root`, `spinner`, `label` | `data-color`: green, red, orange, primary, grey; `data-size`: xsmall, small, large, xlarge; `data-secondary`; `data-loading` | yes |  |
| Card | `root`, `header`, `title`, `icon`, `actions`, `body` | `data-titled`; `data-actions`; `data-loading` | yes |  |
| Checkbox | `root`, `indicator` | none | no |  |
| Code | `root` | `data-dark` | no |  |
| CopyOnClick | `root`, `status`, `message`, `target` | `data-status`: copied, failed | no | `target` is added to the clicked child; the other parts are the status toast. |
| Dialog | `backdrop`, `viewport`, `container`, `root`, `body`, `content`, `header`, `heading`, `title`, `description`, `spacer`, `footer`, `close`, `closeButton`, `closeIcon`, `icon`, `iconGlyph` | `data-described`; `data-type`: danger, info, success, warning | no | `type` is the state of the `icon` part. |
| DropdownMenu | `trigger`, `positioner`, `root` | `data-context` | no |  |
| DropdownMenuItem | `root`, `icon`, `glyph`, `text`, `label`, `description` | `data-danger`; `data-described` | no |  |
| Empty | `root`, `header`, `media`, `title`, `description`, `content` | `data-size`: default, compact; `data-variant`: default, icon | yes | `variant` is the state of the `media` part. |
| FileInput | `root` | none | no |  |
| Input | `root` | `data-light`; `data-error` | no |  |
| Label | `root` | `data-light` | no |  |
| ListRow | `root` | `data-interactive` | yes |  |
| NewButton | `root`, `content`, `icon` | `data-link` | no | `root` is added to the Button it renders, `content` wraps the icon and label. |
| NumberInput | `root`, `input` | `data-light`; `data-error` | no |  |
| PageHeading | `root`, `text`, `title`, `description`, `actions` | none | yes |  |
| Pagination | `root`, `page` | `data-current` | no | `page` is added to each page Button. |
| ProgressBar | `root`, `indicator` | `data-variant`: meter, page; `data-tone`: neutral, success, warning, danger, info, accent | yes |  |
| Select | `root`, `input`, `trigger`, `positioner`, `popup`, `list`, `item`, `itemLabel`, `indicator`, `empty`, `group`, `groupLabel`, `summary`, `preview` | `data-error`; `data-multiple`; `data-selected`; `data-rich-label`; `data-searching` | no |  |
| Spinner | `root`, `container` | `data-size`: small, base, large; `data-blue`; `data-centered` | yes |  |
| SpinnerOverlay | `root`, `text` | `data-fixed` | no |  |
| Switch | `root`, `control`, `thumb`, `text`, `label`, `description` | `data-described`; `data-position`: start, end | no | `label` is added to the Label it renders. |
| Table | `root`, `empty`, `scroller`, `table`, `head`, `headerRow`, `headerCell`, `body`, `row`, `cell` | `data-fetching` | no |  |
| TextArea | `root` | `data-light`; `data-error` | no |  |
| Tooltip | `root`, `arrow` | `data-side`: top, right, bottom, left; `data-arrow` | no |  |
<!-- /generated:primitives -->

The components are exported from `@pterodactyl/sdk` with their props types: `Button` (with
`Button.Text` and `Button.Danger`), `LinkButton`, `RouterLinkButton`, `NewButton`, `NewLinkButton`,
`Input`, `TextArea`, `NumberInput`, `FileInput`, `Select`, `Checkbox`, `Switch`, `Label`, `Card`,
`ListRow`, `PageHeading`, `Badge`, `Alert`, `ProgressBar`, `Dialog` (with `Dialog.Footer`,
`Dialog.Icon`, `Dialog.Confirm`, `Dialog.ConfirmTrigger`), `Tooltip`, `DropdownMenu`,
`DropdownMenuItem`, `ContextDropdownMenu`, `Table`, `Pagination`, `Empty` and its parts, `Spinner`,
`SpinnerOverlay`, `Avatar`, `Code` and `CopyOnClick`. `ContentBox` and `TitledGreyBox` are thin
wrappers over `Card`.

### Views

`defineTheme({ views })` maps view names to components. Each component is a layer of that view:

| Prop | |
| --- | --- |
| `model` | The view's model: the data it shows and the actions it can take, produced by the engine. Read only. |
| `Default` | The layer below. |
| `className` | Classes the place that rendered the view passed in. |
| `children` | For a layout, the routed page. Otherwise what the place that rendered the view put inside it. |
| `parts` | For the views that publish parts, the parts of the layer below. |
| inputs | The props the view takes, such as the `server` of `dashboard.serverCard`. |

`ViewProps<'name'>` types all of them for a view. A name the SDK does not know, such as a view an
extension defines, is typed loosely: `model` is `unknown`.

<!-- sample: snippets/views.tsx#wrap -->
```tsx
/** Keeps the layer below and frames it. */
function FramedCard({ Default }: ViewProps<'dashboard.serverCard'>) {
    return (
        <div className='rounded-2xl ring-1 ring-primary/30'>
            <Default />
        </div>
    );
}
```
<!-- /sample -->

`Default` takes optional `model`, `className`, `children` and `parts`, which replace what the layer
below receives. `className` is merged, the others are replaced.

<!-- sample: snippets/views.tsx#transform -->
```tsx
/** Hands the layer below another model and an extra class. */
function LoudCard({ model, Default }: ViewProps<'dashboard.serverCard'>) {
    return <Default model={{ ...model, name: model.name.toUpperCase() }} className='gap-6' />;
}
```
<!-- /sample -->

Views with parts publish them: `dashboard.serverCard`, `server.files.details`, `server.files.editor`
and `server.files.manager`. A part receives the model and the view's inputs, typed by
`ViewPartProps<'name'>`. Define parts outside the layer's render function.

<!-- sample: snippets/views.tsx#parts -->
```tsx
/** Replaces one part of classic's card and keeps the others. */
function Uptime({ model }: ViewPartProps<'dashboard.serverCard'>) {
    return <span>{model.state.kind === 'ready' ? model.state.power : 'unknown'}</span>;
}

function CardWithUptime({ Default }: ViewProps<'dashboard.serverCard'>) {
    return <Default parts={{ metrics: Uptime }} />;
}
```
<!-- /sample -->

**Models.** A model is data plus actions. Actions run the panel's own requests, cache updates,
notifications and permission checks, so a view only decides what to show and when to call them.
Some models hold a component of the engine's: `model.Terminal` of `server.terminal` draws the console
output on a canvas, `model.Editor` of `server.files.editor` is the code editor, and auth forms carry
`Captcha`. Give them a sized box and theme them with tokens. The [view catalogue](#view-catalogue)
names every model type; `ViewProps` and the model types document each field.

**Loading.** `lazyView(() => import('./views/AppShell'))`, or `lazyView(importer, 'ExportName')`
for one of several exports of a module, loads a view's chunk when it first renders; the router
preloads it with the route. Views exported by one chunk share its load. While a chunk loads, the
view shows its placeholder; if it fails, the layer below renders. Keep `dist/client.js` small by
making every view lazy except the sign-in layout, which renders before anything else.

**Contexts.** Each view has a context: `app` (every signed-in page), `server` (a server's pages,
`layout.server` included), `account`, `admin` or `auth`. A view renders anywhere its context
exists and throws outside it, such as a server view on the dashboard.

**Placing views.** `<View name="…" />` renders a view: it runs the view's hook where it is placed,
resolves its layers and renders the top one. A page is a composition of blocks, so rearranging a
page is a layer that renders the same blocks in a new arrangement, and a block can move anywhere its
context exists, such as `server.power` into the server layout's header. Views that take inputs need
them: `<View name="dashboard.serverCard" server={server} />`.

<!-- sample: snippets/views.tsx#compose -->
```tsx
/** A page is a composition of blocks: this one lays classic's account cards out in two columns. */
function AccountOverview({ model }: ViewProps<'page.account.overview'>) {
    return (
        <PageContentBlock title='Account'>
            {model.twoFactorRequired && <p role='alert'>Turn on two-factor authentication to continue.</p>}
            <Slot name='account.overview.before' />
            <div className='grid gap-6 lg:grid-cols-2'>
                <View name='account.twoFactor' className='lg:col-span-2' />
                <View name='account.password' />
                <View name='account.email' />
            </div>
            <Slot name='account.overview.after' />
        </PageContentBlock>
    );
}
```
<!-- /sample -->

`<View name="…" optional />` renders nothing when no installed package defines the view. Use it for
the views extensions define, so the theme works with and without them:

<!-- sample: snippets/views.tsx#optional -->
```tsx
/** Places a block a billing extension defines; nothing renders while that extension is not installed. */
function SidebarFooter() {
    return <View name='billing.balance' optional className='mt-auto' />;
}

/** Restyles that block. Its model is `unknown` here: the theme does not know the extension's types. */
function Balance({ Default }: ViewProps<'billing.balance'>) {
    return (
        <div className='glass rounded-xl p-3'>
            <Default />
        </div>
    );
}
```
<!-- /sample -->

**Slots.** A layer that replaces a view renders the view's slots, listed in the catalogue, so the
extensions mounted there keep their place: `<Slot name="server.console.power.before" data={model.server} />`.
The models carry what the slots receive, such as `model.server` and `model.route`. In development,
a layer that renders none of its view's slots logs a warning after three seconds. The before and
after slots of a page are rendered by the engine around the page view, so a layer cannot drop them.

**Layouts and accessibility.** A layout places its `children` inside `<Main>`, which renders
`<main id="main" tabIndex={-1}>`. The engine renders a skip link to it, moves focus to it after
each navigation and announces the new page title. Nested layouts each render `<Main>`; only the
outermost renders the element. In development, a layout that never renders `<Main>` logs a warning.
Beyond that, a view is responsible for its own markup: label controls, name navigation landmarks
(`<nav aria-label="…">`), keep visible focus styles, and spread the `fieldProps` of form models onto
their inputs.

`layout.server` receives its children ready-made: the server's console, which stays mounted while
another server page shows, the current page, and the conflict notice in their place while the
server installs, transfers or is suspended. `model.conflict` says which. `page.server.console`
receives `active` so it can tell whether it is the page on show.

**Opaque views.** The admin pages and `app.search` still render the panel's own containers. Their
model is `OpaqueModel` (empty) and they have no fixtures: wrap them with `Default`, or place blocks
around them.

#### Navigation

The layout models carry their area's navigation: `layout.app` has `links` (the dashboard, the admin
area for root admins, the account) and the others `nav`. `useNavigation(area)` reads the navigation
of `account`, `server` or `admin` anywhere. Items are filtered by subuser permissions and egg rules,
and include extension screens.

| `NavItem` field | |
| --- | --- |
| `id` | The path below the area root (`index` for the root), or an extension screen's id. |
| `label`, `icon`, `group` | The label, a lucide icon name for `NamedIcon` (or null), and the extension screen's group. |
| `to`, `params`, `hash` | The route and its parameters, for `NavigationLink`. |
| `order`, `exact` | Its position, and whether it is active only on its own path. |
| `extensionId` | The extension whose screen it opens; core items have none. |

Render items through `<Navigation items={…}>` or `<Navigation area="…">`. It calls its child with
each item and `{ active, badge }`, inside the item's runtime gate, so an extension screen with a
runtime condition appears only once it allows. `badge` is the screen's live badge. `NavigationLink`
links to an item and carries `data-status="active"` while it is active; `useActiveNavItem(items)`
returns the most specific active item.

<!-- sample: snippets/navigation.tsx#account -->
```tsx
/** The account pages with a vertical menu. `model.nav` lists them, extension screens included. */
export function AccountLayout({ model, children }: ViewProps<'layout.account'>) {
    const current = useActiveNavItem(model.nav.items);

    return (
        <div className='mx-auto flex max-w-panel gap-8 p-6'>
            <nav aria-label='Account' className='w-56 shrink-0 space-y-1'>
                <Slot name='account.navigation.before' />
                <Navigation items={model.nav.items}>
                    {(item, { active, badge }) => (
                        <NavigationLink
                            item={item}
                            className={cn('flex items-center gap-2 rounded-lg px-3 py-2', active && 'bg-primary/15')}
                        >
                            {item.icon && <NamedIcon name={item.icon} size={16} aria-hidden />}
                            <span className='flex-1'>{item.label}</span>
                            {badge}
                        </NavigationLink>
                    )}
                </Navigation>
                <Slot name='account.navigation.after' />
            </nav>
            <Main className='min-w-0 flex-1'>
                <p className='text-sm text-muted-foreground'>{current?.label}</p>
                {children}
            </Main>
        </div>
    );
}
```
<!-- /sample -->

<!-- sample: snippets/navigation.tsx#anywhere -->
```tsx
/** The account links anywhere: `useNavigation` reads an area's live navigation. */
export function AccountShortcuts() {
    const { items } = useNavigation('account');

    return (
        <ul>
            {items.map((item) => (
                <li key={`${item.extensionId ?? ''}:${item.id}`}>
                    <NavigationLink item={item}>{item.label}</NavigationLink>
                </li>
            ))}
        </ul>
    );
}
```
<!-- /sample -->

`PanelLink` links to any panel route with typed parameters, and also carries `data-status="active"`.

#### Schemes, settings and icons

`useTheme()` returns `{ id, chain, scheme, schemes, setScheme }`. `scheme` is the scheme in use;
`setScheme('dark' | 'light' | 'system')` stores the choice in the `pterodactyl_scheme` cookie and
swaps the class on `<html>` without a reload. The server renders the stored scheme on the next
request; `system` follows the device, read before the stylesheet loads. A preference the theme does
not support falls back to its first scheme. The `layout.app` model carries the same `scheme`,
`schemes` and `setScheme`, which is null unless the theme offers both.

`useThemeSettings<ThemeSettings>()` returns the settings of the whole chain.

`defineTheme({ icons })` maps lucide icon names to components that take `LucideProps`.
`<NamedIcon name="…" />`, which draws navigation and extension icons by name, uses the theme's
icon before lucide's.

`@pterodactyl/sdk/theme` is a small entry with what a theme needs to start: `defineTheme`, `skin`,
`useSkin`, `View`, `Slot`, `Main`, the navigation API, `lazyView`, `useTheme`, `useThemeSettings`
and the types. The panel loads the theme before its first render, so keep the modules imported
eagerly to that entry; views that use components from `@pterodactyl/sdk` belong behind
`lazyView`.

#### View catalogue

Every view, with its context, model type, the inputs and parts it takes, the slots it renders and
its fixtures. `npx pterodactyl-theme list` prints the same from `@pterodactyl/sdk/views.json`.

<!-- generated:views -->
#### Layouts

| View | Context | Model | Inputs and parts | Slots | Fixtures |
| --- | --- | --- | --- | --- | --- |
| `layout.account` | account | `AccountLayoutModel` |  | `account.navigation.before` `account.navigation.after` | default, extension |
| `layout.admin` | admin | `AdminLayoutModel` |  | `panel.navigation.before` `panel.navigation.after` | default, extension |
| `layout.app` | app | `AppLayoutModel` |  | `nav.items.before` `nav.items.after` | user, admin, schemes, logo |
| `layout.auth` | auth | `AuthLayoutModel` |  |  | default, logo |
| `layout.server` | server | `ServerLayoutModel` |  | `server.navigation.before` `server.navigation.after` | owner, subuser, admin, installing, suspended |

#### Pages

| View | Context | Model | Inputs and parts | Slots | Fixtures |
| --- | --- | --- | --- | --- | --- |
| `page.account.activity` | account | `ActivityPageModel` |  | `account.activity.before` `account.activity.after` | default |
| `page.account.api` | account | `AccountListPageModel` |  | `account.api.before` `account.api.after` | default, error |
| `page.account.overview` | account | `AccountOverviewModel` |  | `account.overview.before` `account.overview.after` | default, twoFactorRequired |
| `page.account.ssh` | account | `AccountListPageModel` |  | `account.ssh.before` `account.ssh.after` | default, error |
| `page.auth.checkpoint` | auth | `AuthPageModel` |  | `auth.checkpoint.before` `auth.checkpoint.after` | default |
| `page.auth.login` | auth | `AuthPageModel` |  | `auth.login.before` `auth.login.after` | default |
| `page.auth.password` | auth | `AuthPageModel` |  | `auth.password.before` `auth.password.after` | default |
| `page.auth.passwordReset` | auth | `AuthPageModel` |  | `auth.passwordReset.before` `auth.passwordReset.after` | default |
| `page.dashboard` | app | `DashboardModel` |  |  | default, error |
| `page.server.activity` | server | `ActivityPageModel` |  | `server.activity.before` `server.activity.after` | default |
| `page.server.backups` | server | `BackupsPageModel` |  | `server.backups.before` `server.backups.after` | default, loading, error |
| `page.server.console` | server | `ServerConsoleModel` | `active: boolean` | `server.console.before` `server.console.after` | default, installing, maintenance |
| `page.server.databases` | server | `ServerPageModel` |  | `server.databases.before` `server.databases.after` | default, error |
| `page.server.files` | server | `ServerPageModel` |  | `server.files.before` `server.files.after` | default, error |
| `page.server.files.editor` | server | `FileEditorPageModel` |  | `server.files.editor.before` `server.files.editor.after` | file, new, loading, error |
| `page.server.network` | server | `ServerPageModel` |  | `server.network.before` `server.network.after` | default, error |
| `page.server.schedules` | server | `SchedulesPageModel` |  | `server.schedules.before` `server.schedules.after` | default, error |
| `page.server.schedules.detail` | server | `ScheduleDetailPageModel` |  | `server.schedules.detail.before` `server.schedules.detail.after` | default, loading, error |
| `page.server.settings` | server | `ServerSettingsPageModel` |  | `server.settings.before` `server.settings.after` | default |
| `page.server.startup` | server | `StartupPageModel` |  | `server.startup.before` `server.startup.after` `server.startup.form` | default, loading, error |
| `page.server.users` | server | `SubusersPageModel` |  | `server.users.before` `server.users.after` | default, loading, error |
| `page.server.users.create` | server | `SubuserCreatePageModel` |  | `server.users.create.before` `server.users.create.after` | default, loading |

#### Blocks

| View | Context | Model | Inputs and parts | Slots | Fixtures |
| --- | --- | --- | --- | --- | --- |
| `account.apiKeys.create` | account | `ApiKeyCreateModel` |  |  | default, invalid, created |
| `account.apiKeys.list` | account | `ApiKeyListModel` |  |  | keys, empty, loading, deleting |
| `account.email` | account | `AccountEmailModel` |  |  | default, submitting |
| `account.password` | account | `AccountPasswordModel` |  |  | default, invalid |
| `account.sshKeys.create` | account | `SshKeyCreateModel` |  |  | default, submitting |
| `account.sshKeys.list` | account | `SshKeyListModel` |  |  | keys, empty, loading |
| `account.twoFactor` | account | `AccountTwoFactorModel` |  |  | disabled, enabled, setup, setupLoading, disabling, recoveryTokens |
| `activity.log` | app | `ActivityLogModel` | `scope: ActivityScope` |  | account, server, filtered, empty, paged, loading, error |
| `app.search` | app | `OpaqueModel` |  |  |  |
| `auth.checkpointForm` | auth | `CheckpointFormModel` |  |  | code, recovery |
| `auth.forgotPasswordForm` | auth | `ForgotPasswordFormModel` |  |  | default, invalid |
| `auth.loginForm` | auth | `LoginFormModel` |  | `auth.login.form.after` | default, invalid, submitting |
| `auth.resetPasswordForm` | auth | `ResetPasswordFormModel` |  |  | default, mismatch |
| `dashboard.serverCard` | app | `ServerCardModel` | `server: Server`<br>parts: `identity`, `address`, `metrics` | `dashboard.serverRow.name.after` | running, offline, suspended, loading |
| `dashboard.serverList` | app | `ServerListModel` |  | `dashboard.before` `dashboard.after` `dashboard.serverRow.before` `dashboard.serverRow.metrics.after` `dashboard.serverRow.after` | servers, empty, loading |
| `server.allocations` | server | `ServerAllocationsModel` |  |  | allocations, full, unmanaged, readOnly, empty, loading |
| `server.backups` | server | `BackupListModel` |  |  | backups, empty, full, noLimit, paged, pastEnd, readOnly |
| `server.conflict` | server | `ServerConflictModel` |  |  | installing, suspended, maintenance, transferring, restoring |
| `server.databases` | server | `ServerDatabasesModel` |  |  | databases, full, empty, noLimit, readOnly, loading |
| `server.debug` | server | `ServerDebugModel` |  |  | default |
| `server.feature.eula` | server | `EulaPromptModel` |  |  | closed, open, accepting |
| `server.feature.gslToken` | server | `GslTokenPromptModel` |  |  | closed, open |
| `server.feature.hytaleOauth` | server | `HytaleOauthPromptModel` |  |  | closed, open |
| `server.feature.javaVersion` | server | `JavaVersionPromptModel` |  |  | closed, open, loading, noPermission |
| `server.feature.pidLimit` | server | `LimitPromptModel` |  |  | closed, admin, user |
| `server.feature.steamDiskSpace` | server | `LimitPromptModel` |  |  | closed, admin, user |
| `server.features` | server | `ServerFeaturesModel` |  |  | minecraft, none |
| `server.files.details` | server | `FileDetailsModel` | `file: FileObject`<br>parts: `icon`, `name`, `size`, `modified` |  | file, directory |
| `server.files.editor` | server | `FileEditorModel` | parts: `notice`, `editor`, `language`, `actions` |  | file, dirty, saving, readOnly, pteroignore, new |
| `server.files.manager` | server | `FileManagerModel` | parts: `toolbar`, `list`, `selection` | `server.files.toolbar` `server.files.rowActions` `server.files.selectionActions` | directory, selected, uploading, readOnly, empty, loading |
| `server.graphs` | server | `ServerGraphsModel` |  |  | running, offline |
| `server.power` | server | `ServerPowerModel` |  | `server.console.power.before` `server.console.power.after` | running, offline, starting, stopping, disconnected, noPermission |
| `server.reinstall` | server | `ServerReinstallModel` |  |  | default, skipped, denied |
| `server.rename` | server | `ServerRenameModel` |  |  | default, empty, denied |
| `server.schedule` | server | `ScheduleModel` |  |  | active, processing, inactive, readOnly |
| `server.schedule.tasks` | server | `ScheduleTasksModel` |  |  | tasks, empty, readOnly, noBackups |
| `server.schedules` | server | `ScheduleListModel` |  |  | schedules, empty, readOnly, loading |
| `server.sftp` | server | `ServerSftpModel` |  |  | default, ipv6, denied |
| `server.startup.command` | server | `StartupCommandModel` |  |  | default |
| `server.startup.image` | server | `StartupImageModel` |  |  | selectable, single, custom, pending |
| `server.startup.variables` | server | `StartupVariablesModel` |  |  | variables, readOnly, empty |
| `server.stats` | server | `ServerStatsModel` |  |  | running, offline, starting, alarms, unlimited |
| `server.subuserForm` | server | `SubuserFormModel` | `subuser?: SubuserSummary`, `onClose?: () => void` | `server.users.permissions.before` | create, edit, restricted, viewOnly, loading |
| `server.subusers` | server | `SubuserListModel` |  |  | subusers, empty, readOnly |
| `server.terminal` | server | `ServerTerminalModel` |  |  | running, connecting, failed, scrolledUp, readOnly |

#### Opaque pages

These pages still render the panel's own containers: their model is `OpaqueModel` (empty), they have no
fixtures, and `Default` renders the container. Wrap them, or place blocks around them.

| View | Slots the engine renders around it |
| --- | --- |
| `page.admin.activity` | `panel.activity.before` `panel.activity.after` |
| `page.admin.apiKeys` | `panel.apiKeys.before` `panel.apiKeys.after` |
| `page.admin.databaseHosts` | `panel.databaseHosts.before` `panel.databaseHosts.after` |
| `page.admin.databaseHosts.create` | `panel.databaseHosts.create.before` `panel.databaseHosts.create.after` |
| `page.admin.databaseHosts.detail` | `panel.databaseHosts.detail.before` `panel.databaseHosts.detail.after` |
| `page.admin.eggs` | `panel.eggs.before` `panel.eggs.after` |
| `page.admin.eggs.catalog` |  |
| `page.admin.eggs.create` | `panel.eggs.create.before` `panel.eggs.create.after` |
| `page.admin.eggs.detail` | `panel.eggs.detail.before` `panel.eggs.detail.after` |
| `page.admin.eggs.detail.configuration` | `panel.eggs.detail.configuration.before` `panel.eggs.detail.configuration.after` |
| `page.admin.eggs.detail.script` | `panel.eggs.detail.script.before` `panel.eggs.detail.script.after` |
| `page.admin.eggs.detail.tags` | `panel.eggs.detail.tags.before` `panel.eggs.detail.tags.after` |
| `page.admin.eggs.detail.variables` | `panel.eggs.detail.variables.before` `panel.eggs.detail.variables.after` |
| `page.admin.extensions` | `panel.extensions.before` `panel.extensions.after` |
| `page.admin.locations` | `panel.locations.before` `panel.locations.after` |
| `page.admin.locations.detail` | `panel.locations.detail.before` `panel.locations.detail.after` |
| `page.admin.mounts` | `panel.mounts.before` `panel.mounts.after` |
| `page.admin.mounts.create` | `panel.mounts.create.before` `panel.mounts.create.after` |
| `page.admin.mounts.detail` | `panel.mounts.detail.before` `panel.mounts.detail.after` |
| `page.admin.nodes` | `panel.nodes.before` `panel.nodes.after` |
| `page.admin.nodes.create` | `panel.nodes.create.before` `panel.nodes.create.after` |
| `page.admin.nodes.detail` | `panel.nodes.detail.before` `panel.nodes.detail.after` |
| `page.admin.nodes.detail.about` | `panel.nodes.detail.about.before` `panel.nodes.detail.about.after` |
| `page.admin.nodes.detail.allocations` | `panel.nodes.detail.allocations.before` `panel.nodes.detail.allocations.after` |
| `page.admin.nodes.detail.configuration` | `panel.nodes.detail.configuration.before` `panel.nodes.detail.configuration.after` |
| `page.admin.nodes.detail.servers` | `panel.nodes.detail.servers.before` `panel.nodes.detail.servers.after` |
| `page.admin.nodes.detail.settings` | `panel.nodes.detail.settings.before` `panel.nodes.detail.settings.after` |
| `page.admin.nodes.detail.tags` | `panel.nodes.detail.tags.before` `panel.nodes.detail.tags.after` |
| `page.admin.overview` |  |
| `page.admin.servers` | `panel.servers.before` `panel.servers.after` |
| `page.admin.servers.create` | `panel.servers.create.before` `panel.servers.create.after` |
| `page.admin.servers.detail` | `panel.servers.detail.before` `panel.servers.detail.after` |
| `page.admin.servers.detail.about` | `panel.servers.detail.about.before` `panel.servers.detail.about.after` |
| `page.admin.servers.detail.build` | `panel.servers.detail.build.before` `panel.servers.detail.build.after` |
| `page.admin.servers.detail.databases` | `panel.servers.detail.databases.before` `panel.servers.detail.databases.after` |
| `page.admin.servers.detail.delete` | `panel.servers.detail.delete.before` `panel.servers.detail.delete.after` |
| `page.admin.servers.detail.details` | `panel.servers.detail.details.before` `panel.servers.detail.details.after` |
| `page.admin.servers.detail.manage` | `panel.servers.detail.manage.before` `panel.servers.detail.manage.after` |
| `page.admin.servers.detail.mounts` | `panel.servers.detail.mounts.before` `panel.servers.detail.mounts.after` |
| `page.admin.servers.detail.startup` | `panel.servers.detail.startup.before` `panel.servers.detail.startup.after` |
| `page.admin.settings` | `panel.settings.before` `panel.settings.after` |
| `page.admin.settings.advanced` | `panel.settings.before` `panel.settings.after` |
| `page.admin.settings.mail` | `panel.settings.before` `panel.settings.after` |
| `page.admin.tags` | `panel.tags.before` `panel.tags.after` |
| `page.admin.themes` | `panel.themes.before` `panel.themes.after` |
| `page.admin.themeWorkbench` |  |
| `page.admin.users` | `panel.users.before` `panel.users.after` |
| `page.admin.users.create` | `panel.users.create.before` `panel.users.create.after` |
| `page.admin.users.detail` | `panel.users.detail.before` `panel.users.detail.after` |
| `page.admin.users.detail.about` |  |
<!-- /generated:views -->

### Recipes

The recipes are files of the Aurora sample theme, which overrides these views:

<!-- sample: aurora/src/theme.tsx -->
```tsx
import { Boxes } from 'lucide-react';
import { defineTheme, lazyView } from '@pterodactyl/sdk/theme';
import { auroraSkins } from './skins';
import AuthLayout from './views/AuthLayout';

export default defineTheme({
    ui: auroraSkins,
    views: {
        // The sign-in screen renders without waiting for a chunk.
        'layout.auth': AuthLayout,
        'auth.loginForm': lazyView(() => import('./views/LoginForm')),
        'layout.app': lazyView(() => import('./views/AppShell')),
        'layout.server': lazyView(() => import('./views/ServerLayout')),
        'server.power': lazyView(() => import('./views/Power')),
        'page.server.console': lazyView(() => import('./views/ConsolePage')),
        'server.terminal': lazyView(() => import('./views/Terminal')),
        'server.stats': lazyView(() => import('./views/Stats')),
        'dashboard.serverList': lazyView(() => import('./views/ServerGrid')),
        'dashboard.serverCard': lazyView(() => import('./views/ServerCard')),
    },
    // Every <NamedIcon name="server"/> draws this in place of lucide's, such as a navigation item naming it.
    icons: { server: Boxes },
});
```
<!-- /sample -->

#### A sidebar shell

`layout.app` frames every signed-in page. This one is a glass sidebar on large screens and a top
bar below them; the `sidebar` setting moves it to the right through the `sidebar-right:` variant.
It keeps the `nav.items.before` and `nav.items.after` slots, places `app.search`, and puts the
page inside `<Main>`.

<!-- sample: aurora/src/views/AppShell.tsx -->
```tsx
import { LayoutGrid, LogOut, Shield, UserRound, type LucideIcon } from 'lucide-react';
import { Avatar, PanelLink, SpinnerOverlay, Tooltip, cn } from '@pterodactyl/sdk';
import { Main, Slot, View, type AppLayoutLink, type AppLayoutModel, type ViewProps } from '@pterodactyl/sdk/theme';
import SchemeSwitch from '../SchemeSwitch';

const icons: Record<AppLayoutLink['id'], LucideIcon> = { dashboard: LayoutGrid, admin: Shield, account: UserRound };

const linkClass = cn(
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted-foreground no-underline transition',
    'hover:bg-popover hover:text-foreground data-[status=active]:bg-primary/15 data-[status=active]:text-foreground'
);

function Brand({ brand }: { brand: AppLayoutModel['brand'] }) {
    return (
        <PanelLink destination={{ to: '/' }} className='flex h-10 items-center px-3 font-semibold no-underline'>
            {brand.logoUrl ? <img src={brand.logoUrl} alt={brand.name} className='h-8 w-auto' /> : brand.name}
        </PanelLink>
    );
}

function Links({ model }: { model: AppLayoutModel }) {
    return (
        <>
            <Slot name='nav.items.before' />
            {model.links.map((link) => {
                const Glyph = icons[link.id];

                return (
                    <PanelLink key={link.id} destination={link.destination} exact={link.exact} className={linkClass}>
                        <Glyph className='size-4' aria-hidden />
                        <span className='max-lg:sr-only'>{link.label}</span>
                    </PanelLink>
                );
            })}
            <Slot name='nav.items.after' />
        </>
    );
}

/** A sidebar on large screens and a top bar below them; the "sidebar" setting picks the side. */
export default function AppShell({ model, children }: ViewProps<'layout.app'>) {
    return (
        <div className='flex min-h-dvh flex-col lg:flex-row sidebar-right:lg:flex-row-reverse'>
            <SpinnerOverlay visible={model.signingOut} fixed />
            <aside className='glass flex shrink-0 items-center gap-2 p-3 lg:sticky lg:top-0 lg:h-dvh lg:w-sidebar lg:flex-col lg:items-stretch lg:gap-6 lg:p-4'>
                <Brand brand={model.brand} />
                <nav aria-label='Main' className='flex flex-1 gap-1 lg:flex-none lg:flex-col'>
                    <Links model={model} />
                </nav>
                <View name='app.search' className={linkClass} />
                <div className='flex items-center gap-2 lg:mt-auto'>
                    <Avatar name={model.user.uuid} size={28} />
                    <span className='min-w-0 flex-1 truncate text-sm max-lg:sr-only'>{model.user.username}</span>
                    <SchemeSwitch />
                    <Tooltip content='Sign out'>
                        <button type='button' aria-label='Sign out' onClick={model.signOut} className={linkClass}>
                            <LogOut className='size-4' aria-hidden />
                        </button>
                    </Tooltip>
                </div>
            </aside>
            <Main className='min-w-0 flex-1'>{children}</Main>
        </div>
    );
}
```
<!-- /sample -->

#### Power controls in the server header

`server.power` is a block, so the server layout can place it in its header. Its model says what the
user may do (`permissions`) and what the server allows now (`enabled`); its actions send the signal
over the console connection. The header hides it while the server is in a conflict state.

<!-- sample: aurora/src/views/ServerLayout.tsx -->
```tsx
import { NamedIcon, cn } from '@pterodactyl/sdk';
import { Main, Navigation, NavigationLink, Slot, View, type ViewProps } from '@pterodactyl/sdk/theme';

const tabClass =
    'flex shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-sm text-muted-foreground no-underline hover:text-foreground';

/** The server's name with its power controls in the header, then its pages as tabs. */
export default function ServerLayout({ model, children }: ViewProps<'layout.server'>) {
    const items = model.adminLink ? [...model.nav.items, model.adminLink] : model.nav.items;

    return (
        <div className='mx-auto w-full max-w-panel px-4 py-6 lg:px-8'>
            <header className='glass mb-4 flex flex-wrap items-center gap-4 rounded-2xl px-5 py-4'>
                <div className='min-w-0 flex-1'>
                    <h1 className='truncate text-xl font-semibold'>{model.name}</h1>
                    <p className='font-mono text-xs text-muted-foreground'>{model.identifier}</p>
                </div>
                {/* Power controls make no sense while the server installs, transfers or restores. */}
                {model.conflict === null && <View name='server.power' />}
            </header>
            <nav aria-label='Server' className='mb-6 flex gap-1 overflow-x-auto'>
                <Slot name='server.navigation.before' data={model.server} />
                <Navigation items={items}>
                    {(item, { active, badge }) => (
                        <NavigationLink item={item} className={cn(tabClass, active && 'bg-primary/15 text-foreground')}>
                            {item.icon && <NamedIcon name={item.icon} size={16} aria-hidden />}
                            {item.label}
                            {badge}
                        </NavigationLink>
                    )}
                </Navigation>
                <Slot name='server.navigation.after' data={model.server} />
            </nav>
            <Main>{children}</Main>
        </div>
    );
}
```
<!-- /sample -->

<!-- sample: aurora/src/views/Power.tsx -->
```tsx
import { Play, RotateCcw, Square, Zap } from 'lucide-react';
import { Button, Dialog } from '@pterodactyl/sdk';
import { Slot, type ViewProps } from '@pterodactyl/sdk/theme';

/**
 * Compact power controls. The model says what the user may do and what the server allows right now;
 * stopping a server that is already stopping kills it, so that asks first.
 */
export default function Power({ model }: ViewProps<'server.power'>) {
    const { permissions, enabled, actions } = model;

    return (
        <div className='flex items-center gap-2'>
            <Slot name='server.console.power.before' data={model.server} />
            {permissions.start && (
                <Button size='small' color='green' disabled={!enabled.start} onClick={actions.start}>
                    <Play className='size-4' aria-hidden /> Start
                </Button>
            )}
            {permissions.restart && (
                <Button size='small' color='grey' disabled={!enabled.restart} onClick={actions.restart}>
                    <RotateCcw className='size-4' aria-hidden /> Restart
                </Button>
            )}
            {permissions.stop &&
                (model.killable ? (
                    <Dialog.ConfirmTrigger
                        title='Kill the server process?'
                        confirm='Kill'
                        trigger={({ onClick }) => (
                            <Button size='small' color='red' disabled={!enabled.kill} onClick={onClick}>
                                <Zap className='size-4' aria-hidden /> Kill
                            </Button>
                        )}
                        onConfirmed={(_event, close) => {
                            close();
                            actions.kill();
                        }}
                    >
                        Killing a server can corrupt its data.
                    </Dialog.ConfirmTrigger>
                ) : (
                    <Button size='small' color='red' disabled={!enabled.stop} onClick={actions.stop}>
                        <Square className='size-4' aria-hidden /> Stop
                    </Button>
                ))}
            <Slot name='server.console.power.after' data={model.server} />
        </div>
    );
}
```
<!-- /sample -->

#### A card-grid dashboard

`dashboard.serverList` owns the dashboard's list: servers, pagination, the scope switch for root
admins and the empty state. This layer lays the servers out as cards. Each card places
`dashboard.serverCard`, whose model carries the server's usage as the panel polls it, and adds power
buttons outside the card's link through `useSendServerPower`, which needs no console connection.

<!-- sample: aurora/src/views/ServerGrid.tsx -->
```tsx
import { Play, RotateCcw, Server as ServerIcon, Square } from 'lucide-react';
import {
    Button,
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    PageHeading,
    PanelLink,
    Spinner,
    Switch,
    useSendServerPower,
    type SdkPowerSignal,
} from '@pterodactyl/sdk';
import { Slot, View, type ServerListItem, type ServerListModel, type ViewProps } from '@pterodactyl/sdk/theme';

/**
 * Sends a signal through the client API, so it works without a console connection. The panel checks
 * the user's control permissions and shows its own notice when the request fails.
 */
function PowerButtons({ item }: { item: ServerListItem }) {
    const power = useSendServerPower(item.server.attributes.uuid);
    const send = (signal: SdkPowerSignal) => () => void power.mutateAsync({ signal }).catch(() => {});
    const running = item.power !== null && item.power !== 'offline';

    return (
        <div className='flex gap-2'>
            {running ? (
                <>
                    <Button size='xsmall' color='grey' disabled={power.isPending} onClick={send('restart')}>
                        <RotateCcw className='size-3.5' aria-hidden /> Restart
                    </Button>
                    <Button size='xsmall' color='red' disabled={power.isPending} onClick={send('stop')}>
                        <Square className='size-3.5' aria-hidden /> Stop
                    </Button>
                </>
            ) : (
                <Button
                    size='xsmall'
                    color='green'
                    disabled={power.isPending || item.power === null}
                    onClick={send('start')}
                >
                    <Play className='size-3.5' aria-hidden /> Start
                </Button>
            )}
        </div>
    );
}

function Tile({ item }: { item: ServerListItem }) {
    const { server } = item;

    return (
        <li className='glass flex flex-col gap-4 rounded-2xl p-5'>
            <Slot name='dashboard.serverRow.before' data={server} />
            <PanelLink
                destination={{ to: '/server/$id', params: { id: server.attributes.identifier } }}
                aria-label={server.attributes.name}
                className='text-foreground no-underline'
            >
                <View name='dashboard.serverCard' server={server} />
            </PanelLink>
            <Slot name='dashboard.serverRow.metrics.after' data={server} />
            <PowerButtons item={item} />
            <Slot name='dashboard.serverRow.after' data={server} />
        </li>
    );
}

function Pager({ model }: { model: ServerListModel }) {
    const { pagination, actions } = model;

    if (!pagination || pagination.totalPages < 2) {
        return null;
    }

    return (
        <nav aria-label='Pages' className='mt-6 flex items-center justify-center gap-3 text-sm'>
            <Button
                size='xsmall'
                color='grey'
                disabled={pagination.currentPage === 1}
                onClick={() => actions.setPage(pagination.currentPage - 1)}
            >
                Previous
            </Button>
            {pagination.currentPage} of {pagination.totalPages}
            <Button
                size='xsmall'
                color='grey'
                disabled={pagination.currentPage === pagination.totalPages}
                onClick={() => actions.setPage(pagination.currentPage + 1)}
            >
                Next
            </Button>
        </nav>
    );
}

/** The dashboard's servers as a grid of cards, each with power buttons outside its link. */
export default function ServerGrid({ model }: ViewProps<'dashboard.serverList'>) {
    const others = model.scope === 'others';

    return (
        <>
            <PageHeading
                title='Servers'
                actions={
                    model.canViewOthers && (
                        <Switch
                            checked={others}
                            onChange={() => model.actions.setScope(others ? 'mine' : 'others')}
                            label="Show other users' servers"
                            controlPosition='end'
                        />
                    )
                }
            />
            <Slot name='dashboard.before' />
            {model.loading && <Spinner size='large' centered />}
            {!model.loading && model.servers.length === 0 && (
                <Empty className='glass rounded-2xl'>
                    <EmptyHeader>
                        <EmptyMedia variant='icon'>
                            <ServerIcon />
                        </EmptyMedia>
                        <EmptyTitle>No servers yet</EmptyTitle>
                        <EmptyDescription>Servers you own or are invited to show up here.</EmptyDescription>
                    </EmptyHeader>
                </Empty>
            )}
            <ul className='grid gap-4 sm:grid-cols-2 xl:grid-cols-3'>
                {model.servers.map((item) => (
                    <Tile key={item.server.attributes.uuid} item={item} />
                ))}
            </ul>
            <Pager model={model} />
            <Slot name='dashboard.after' />
        </>
    );
}
```
<!-- /sample -->

<!-- sample: aurora/src/views/ServerCard.tsx -->
```tsx
import { Badge, ProgressBar, Spinner, bytesToString } from '@pterodactyl/sdk';
import { Slot, useThemeSettings, type ServerCardMetric, type ViewProps } from '@pterodactyl/sdk/theme';
import type { ThemeSettings } from '../theme-types';

/** A limit of 0 is unlimited, which has no percentage to show. */
const percent = ({ value, limit }: ServerCardMetric) => (limit > 0 ? Math.min((value / limit) * 100, 100) : null);

function Usage({ label, text, metric }: { label: string; text: string; metric: ServerCardMetric }) {
    return (
        <div className='space-y-1'>
            {text}
            <ProgressBar value={percent(metric)} label={label} tone={metric.alarm ? 'danger' : 'accent'} />
        </div>
    );
}

/** A server's name and address, with its usage as the panel polls it (every 20 seconds at most). */
export default function ServerCard({ model, server }: ViewProps<'dashboard.serverCard'>) {
    const { liveStats } = useThemeSettings<ThemeSettings>();
    const { state } = model;

    return (
        <div className='space-y-3'>
            <div className='flex items-start gap-2'>
                <div className='min-w-0 flex-1'>
                    <p className='truncate font-semibold'>{model.name}</p>
                    <p className='truncate font-mono text-xs text-muted-foreground'>{model.address ?? 'No address'}</p>
                </div>
                <Slot name='dashboard.serverRow.name.after' data={server} />
                {state.kind === 'ready' && (
                    <Badge tone={state.power === 'running' ? 'success' : 'neutral'} variant='soft'>
                        {state.power}
                    </Badge>
                )}
                {state.kind === 'unavailable' && <Badge tone='warning'>{state.reason.replace('-', ' ')}</Badge>}
            </div>
            {state.kind === 'loading' && <Spinner size='small' />}
            {liveStats && state.kind === 'ready' && (
                <div className='grid grid-cols-3 gap-3 text-xs text-muted-foreground'>
                    <Usage label='CPU' text={`CPU ${state.cpu.value.toFixed(0)}%`} metric={state.cpu} />
                    <Usage label='Memory' text={bytesToString(state.memory.value)} metric={state.memory} />
                    <Usage label='Disk' text={bytesToString(state.disk.value)} metric={state.disk} />
                </div>
            )}
        </div>
    );
}
```
<!-- /sample -->

#### A rebuilt console

`page.server.console` composes the console from blocks. This one drops `server.power`, which the
header shows, and puts the stats beside the terminal. `server.terminal` hands over the canvas as
`model.Terminal` and the command line as `model.command`; `server.stats` updates live while the
server runs.

<!-- sample: aurora/src/views/ConsolePage.tsx -->
```tsx
import { Alert, ServerContentBlock } from '@pterodactyl/sdk';
import { Slot, View, type ServerConsoleModel, type ViewProps } from '@pterodactyl/sdk/theme';

const notices: Record<NonNullable<ServerConsoleModel['notice']>, string> = {
    maintenance: 'This node is under maintenance, so the server cannot be controlled.',
    installing: 'The server is installing; most actions wait until it finishes.',
    transferring: 'The server is moving to another node.',
};

/**
 * The console without its power controls, which the server layout shows in its header. The page stays
 * mounted while another server page shows, so it only titles the document while `active`.
 */
export default function ConsolePage({ model, active }: ViewProps<'page.server.console'>) {
    return (
        <ServerContentBlock title={active ? 'Console' : undefined}>
            {model.notice && (
                <Alert type='warning' className='mb-4'>
                    {notices[model.notice]}
                </Alert>
            )}
            <Slot name='server.console.before' data={model.server} />
            <div className='grid gap-4 xl:grid-cols-[minmax(0,1fr)_18rem]'>
                <View name='server.terminal' />
                <View name='server.stats' />
            </div>
            <div className='mt-4 grid gap-4 md:grid-cols-3'>
                <View name='server.graphs' />
            </div>
            <Slot name='server.console.after' data={model.server} />
            <View name='server.features' />
        </ServerContentBlock>
    );
}
```
<!-- /sample -->

<!-- sample: aurora/src/views/Terminal.tsx -->
```tsx
import type { KeyboardEvent } from 'react';
import { Button, Spinner } from '@pterodactyl/sdk';
import type { ServerConsoleCommand, ViewProps } from '@pterodactyl/sdk/theme';

function onKey(command: ServerConsoleCommand, event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === 'Enter') {
        command.submit();
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        command.previous();
    } else if (event.key === 'ArrowDown') {
        command.next();
    }
}

/** The engine draws the output on `model.Terminal`; the theme sizes it and owns the command line. */
export default function Terminal({ model }: ViewProps<'server.terminal'>) {
    const { command } = model;

    return (
        <section aria-label='Console' className='glass flex h-[30rem] flex-col overflow-hidden rounded-2xl'>
            <div className='relative min-h-0 flex-1 bg-terminal p-3 font-mono text-sm'>
                <model.Terminal />
                {model.loading && (
                    <div className='absolute inset-0 grid place-items-center'>
                        <Spinner size='large' />
                    </div>
                )}
                {model.failed && (
                    <div role='alert' className='absolute inset-0 grid place-items-center gap-2 text-sm'>
                        The console could not be loaded.
                        <Button size='xsmall' color='grey' onClick={model.retry}>
                            Try again
                        </Button>
                    </div>
                )}
                {model.scrolledUp && (
                    <Button
                        size='xsmall'
                        color='grey'
                        className='absolute right-5 bottom-3'
                        onClick={model.scrollToBottom}
                    >
                        Latest output
                    </Button>
                )}
            </div>
            {command.allowed && (
                <input
                    aria-label='Console command'
                    placeholder='Type a command'
                    autoCapitalize='none'
                    autoCorrect='off'
                    disabled={command.disabled}
                    value={command.value}
                    onChange={(event) => command.setValue(event.currentTarget.value)}
                    onKeyDown={(event) => onKey(command, event)}
                    className='border-0 border-t border-(--glass-border) bg-transparent px-4 py-3 font-mono text-sm focus:ring-0'
                />
            )}
        </section>
    );
}
```
<!-- /sample -->

<!-- sample: aurora/src/views/Stats.tsx -->
```tsx
import { Badge, Card, ProgressBar, bytesToString } from '@pterodactyl/sdk';
import type { ServerStatMetric, UiTone, ViewProps } from '@pterodactyl/sdk/theme';

const tones: Record<NonNullable<ServerStatMetric['alarm']>, UiTone> = { warning: 'warning', critical: 'danger' };

/** A limit of 0 is unlimited, which has no percentage to show. */
const percent = ({ value, limit }: ServerStatMetric) => (limit > 0 ? Math.min((value / limit) * 100, 100) : null);

function Meter({
    label,
    metric,
    format,
}: {
    label: string;
    metric: ServerStatMetric;
    format: (value: number) => string;
}) {
    return (
        <div className='space-y-1.5'>
            <div className='flex justify-between text-xs'>
                <span className='text-muted-foreground'>{label}</span>
                <span>
                    {format(metric.value)}
                    {metric.limit > 0 && <span className='text-muted-foreground'> / {format(metric.limit)}</span>}
                </span>
            </div>
            <ProgressBar value={percent(metric)} label={label} tone={metric.alarm ? tones[metric.alarm] : 'accent'} />
        </div>
    );
}

/** Live values from the server's websocket; they update several times a second while it runs. */
export default function Stats({ model }: ViewProps<'server.stats'>) {
    return (
        <Card
            title='Resources'
            actions={<Badge tone={model.state === 'running' ? 'success' : 'neutral'}>{model.state ?? 'unknown'}</Badge>}
        >
            <div className='space-y-4'>
                <Meter label='CPU' metric={model.cpu} format={(value) => `${value.toFixed(1)}%`} />
                <Meter label='Memory' metric={model.memory} format={bytesToString} />
                <Meter label='Disk' metric={model.disk} format={bytesToString} />
                <p className='font-mono text-xs text-muted-foreground'>{model.address ?? 'No allocation'}</p>
            </div>
        </Card>
    );
}
```
<!-- /sample -->

#### A split sign-in page

`layout.auth` frames the sign-in pages. This one shows the brand on one side and the form on the
other; it imports only `@pterodactyl/sdk/theme`, so it renders without loading the full SDK.

<!-- sample: aurora/src/views/AuthLayout.tsx -->
```tsx
import { Main, useThemeSettings, type ViewProps } from '@pterodactyl/sdk/theme';
import type { ThemeSettings } from '../theme-types';
import SchemeSwitch from '../SchemeSwitch';

/**
 * A split screen: the brand on one side, the sign-in pages on the other. It imports nothing from
 * `@pterodactyl/sdk`, so the sign-in screen renders without loading the full SDK first.
 */
export default function AuthLayout({ model, children }: ViewProps<'layout.auth'>) {
    const { tagline } = useThemeSettings<ThemeSettings>();

    return (
        <div className='grid min-h-dvh lg:grid-cols-2'>
            <section className='relative hidden flex-col justify-between overflow-hidden bg-primary p-12 text-primary-foreground lg:flex'>
                {model.brand.logoUrl ? (
                    <img src={model.brand.logoUrl} alt={model.brand.name} className='h-10 w-auto self-start' />
                ) : (
                    <p className='text-2xl font-semibold'>{model.brand.name}</p>
                )}
                <p className='max-w-sm text-4xl leading-tight font-semibold text-balance'>{tagline}</p>
            </section>
            <div className='flex flex-col'>
                <div className='flex justify-end p-4'>
                    <SchemeSwitch />
                </div>
                <Main className='flex flex-1 items-center justify-center p-6'>
                    <div className='w-full max-w-sm'>{children}</div>
                </Main>
            </div>
        </div>
    );
}
```
<!-- /sample -->

`auth.loginForm` keeps its security behaviour as long as the layer spreads each field's
`fieldProps` (its name, type, autocomplete and handlers) onto an input, submits through
`model.submit`, renders `Captcha` when the panel requires one, and keeps the
`auth.login.form.after` slot. The engine fetches the CSRF cookie, signs in, opens the two-factor
step and redirects.

<!-- sample: aurora/src/views/LoginForm.tsx -->
```tsx
import { useId, type FormEvent } from 'react';
import { Button, Input, Label, PanelLink, type FieldModel } from '@pterodactyl/sdk';
import { Slot, type ViewProps } from '@pterodactyl/sdk/theme';

/**
 * Spreads the engine's `fieldProps` (name, type, autocomplete, value and handlers) onto each input,
 * so password managers, validation and the disabled state while signing in keep working.
 */
function Field({ field, label }: { field: FieldModel; label: string }) {
    const id = useId();

    return (
        <div className='space-y-1.5'>
            <Label htmlFor={id}>{label}</Label>
            <Input
                {...field.fieldProps}
                id={id}
                $hasError={field.error !== null}
                aria-invalid={field.error !== null || undefined}
                aria-describedby={field.error ? `${id}-error` : undefined}
            />
            {field.error && (
                <p id={`${id}-error`} className='input-help error'>
                    {field.error}
                </p>
            )}
        </div>
    );
}

/** The engine owns the request, the CSRF cookie, the captcha, two-factor and the redirect. */
export default function LoginForm({ model }: ViewProps<'auth.loginForm'>) {
    const { fields, Captcha } = model;
    const submit = (event: FormEvent) => {
        event.preventDefault();
        model.submit();
    };

    return (
        <form onSubmit={submit} className='space-y-5'>
            <h1 className='text-2xl font-semibold'>Sign in</h1>
            <Field field={fields.username} label='Username or email' />
            <Field field={fields.password} label='Password' />
            {/* A panel with reCAPTCHA turned on cannot sign anyone in without it. */}
            {Captcha && <Captcha />}
            <Button type='submit' size='xlarge' disabled={!model.canSubmit} isLoading={model.submitting}>
                Sign in
            </Button>
            <Slot name='auth.login.form.after' data={model.route} />
            <PanelLink destination={model.forgotPassword} className='block text-center text-sm text-muted-foreground'>
                Forgot your password?
            </PanelLink>
        </form>
    );
}
```
<!-- /sample -->

#### Glass

A `glass` utility in the stylesheet, which reads the `blur` setting, and skins that use it for the
surfaces:

<!-- sample: aurora/src/theme.css#glass -->
```css
/* A frosted surface for skins and views: `glass`. The "blur" setting writes --aurora-blur. */
@utility glass {
    background-color: var(--glass);
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(var(--aurora-blur, 16px)) saturate(1.4);
}

@layer base {
    body {
        background:
            radial-gradient(60rem 40rem at 10% -10%, color-mix(in oklab, var(--primary) 22%, transparent), transparent),
            radial-gradient(50rem 30rem at 110% 0%, color-mix(in oklab, var(--accent) 16%, transparent), transparent),
            var(--background);
        background-attachment: fixed;
    }
}

@media (prefers-reduced-transparency: reduce) {
    :root {
        --glass: var(--card);
    }
}
```
<!-- /sample -->

<!-- sample: aurora/src/skins.ts#glass -->
```ts
/** Frosted surfaces. Each recipe here replaces classic's for that part. */
const glass = {
    Card: skin('Card', {
        root: {
            base: 'glass rounded-2xl shadow-xl',
            variants: { titled: { false: 'p-5' }, loading: { true: 'relative' } },
        },
        header: 'flex items-center gap-3 border-b border-(--glass-border) px-5 py-3',
        title: 'text-sm font-semibold text-foreground',
        body: 'p-5',
    }),
    ListRow: skin('ListRow', {
        root: {
            base: 'glass flex items-center rounded-xl p-4 text-foreground no-underline',
            variants: { interactive: { true: 'transition hover:-translate-y-0.5 hover:shadow-lg' } },
        },
    }),
    DropdownMenu: skin('DropdownMenu', {
        root: [
            'glass min-w-56 rounded-xl p-1.5 text-sm text-popover-foreground shadow-xl outline-hidden',
            'origin-(--transform-origin) transition duration-100',
            'data-starting-style:scale-95 data-starting-style:opacity-0 data-ending-style:scale-95 data-ending-style:opacity-0',
        ].join(' '),
    }),
    Tooltip: skin('Tooltip', { root: 'glass z-9999 rounded-md px-2.5 py-1.5 text-xs text-foreground' }),
} satisfies ThemeSkins;
```
<!-- /sample -->

#### Light, dark and a switch

The stylesheet sets each scheme's tokens in `.dark` and `.light` (see [The stylesheet](#the-stylesheet)),
the manifest lists both schemes, and a switch calls `setScheme`:

<!-- sample: aurora/src/SchemeSwitch.tsx -->
```tsx
import { Monitor, Moon, Sun } from 'lucide-react';
import { useTheme, type ThemeScheme } from '@pterodactyl/sdk/theme';

const options = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
] satisfies { value: ThemeScheme | 'system'; label: string; icon: typeof Sun }[];

/** Light, dark or the system's: stored in a cookie and applied without a reload. */
export default function SchemeSwitch() {
    const { scheme, schemes, setScheme } = useTheme();

    if (schemes.length < 2) {
        return null;
    }

    return (
        <div role='group' aria-label='Colour scheme' className='glass inline-flex rounded-full p-0.5'>
            {options.map(({ value, label, icon: Glyph }) => (
                <button
                    key={value}
                    type='button'
                    aria-label={label}
                    aria-pressed={value === scheme}
                    onClick={() => setScheme(value)}
                    className='rounded-full p-1.5 text-muted-foreground aria-pressed:bg-primary aria-pressed:text-primary-foreground'
                >
                    <Glyph className='size-4' aria-hidden />
                </button>
            ))}
        </div>
    );
}
```
<!-- /sample -->

#### Settings in CSS

The `sidebar` and `density` settings write attributes that the stylesheet reads; the `accent`
setting writes the token the colour roles derive from (see the tokens above).

<!-- sample: aurora/src/theme.css#settings -->
```css
/* `sidebar-right:` applies while the "sidebar" setting is "right". */
@custom-variant sidebar-right (&:where([data-aurora-sidebar='right'] *));

/* The "density" setting retunes the spacing scale every utility reads. */
[data-aurora-density='compact'] {
    --spacing: 0.2rem;
}
```
<!-- /sample -->

### Workbench and inspector

**The workbench**, at `/panel/theme-workbench` for root admins, renders every view from its
fixtures through the full layer chain (classic, the active or previewed theme, extension overrides)
without a backend. Pick a view or a UI primitive from the sidebar; `/` searches, and a filter shows
only the views a theme or extension overrides.

- Each preview renders in a frame at a phone (390 px), tablet (820 px) or desktop (1280 px) width,
  or the full width, in either scheme the theme offers. Dialogs, menus and tooltips open inside the
  frame, and pages title the frame rather than the tab.
- A view's fixtures are tabs; **All side by side** shows them together. A layout can render any
  page with fixtures inside it. Nested views are listed with a fixture picker each.
- The side panel shows the snippet to override the view, the command to eject it, its layers with
  the one that rendered and any that failed, its model and inputs, and its slots.
- The UI kit shows every primitive in its variants under the active skins, with the props of each
  sample and the command that prints classic's skin.

The URL holds the state, so a link reopens the same preview:

| Parameter | |
| --- | --- |
| `view` | A view name: `?view=dashboard.serverCard`. |
| `ui` | A primitive name, shown when there is no `view`: `?ui=Button`. |
| `fixture` | A fixture name, or `*` for all side by side. |
| `scheme` | `dark` or `light`. |
| `width` | `phone`, `tablet`, `desktop` or `full`. |

**The inspector** is available in debug mode (`APP_DEBUG=true`), and to a root admin who opens any
page with `?inspect=1` (`?inspect=0` turns it off again). Turn it on with `Alt+Shift+I` or the round
button at the bottom left; it stays on across reloads of the tab. Hover outlines the view under the
pointer with its name and the layer that rendered it; a click pins it and opens a panel with:

- the enclosing views, to walk up the tree;
- the override snippet to copy, and the eject command for views classic has source for;
- the layers, bottom first, marking the one that rendered and any that failed;
- the model (with a button that copies it as JSON) and inputs;
- the slots, and the views nested inside it;
- the custom properties its CSS reads, and the tokens and attributes the theme settings wrote.

`Esc` unpins, then stops inspecting. The inspector reaches into workbench previews too.

### The theme CLI

The SDK installs a `pterodactyl-theme` binary.

```sh
npx pterodactyl-theme list [--kind layout|page|block] [--json]
npx pterodactyl-theme eject <view> [--out src/views] [--entry src] [--force]
npx pterodactyl-theme skins [primitive]
```

- `list` prints every view with its kind, context and fixtures, marking those classic has source
  for.
- `eject` copies classic's source for a view into `src/views`, with the classic files it imports,
  rewrites the imports to `@pterodactyl/sdk`, and prints the import and `views` entry to add. The
  copy compiles as it is. It refuses to overwrite the view's file without `--force`, and keeps
  shared files an earlier eject copied. Views classic has no source for (the opaque views) cannot be
  ejected; wrap them with `Default`.
- `skins` lists the primitives, and with a name prints classic's skin of it, with the constants it
  reads, ready to paste into the theme.

### Testing themes

The scaffold's `vitest.config.mjs` uses `@pterodactyl/sdk/vitest`, which points the SDK imports at
its real runtime. `@pterodactyl/sdk/testing` renders with a theme and fixtures, without a panel:

- `renderView(name, { theme?, fixture?, model?, input? })` renders a view synchronously, with the
  theme's layers over classic. It reads the named fixture, the view's first one, or `model`; nested
  views read their first fixture. It returns `{ container, unmount }`, and each call replaces what
  the previous one rendered.
- `renderPrimitive(name, props, { theme? })` renders a UI primitive under the theme's skins and
  replacements; the names are those of the workbench's UI kit.
- `preloadTheme(theme)` loads the chunks of the theme's `lazyView` views. `renderView` throws until
  it has resolved, so await it in `beforeAll`.
- `createViewTestHost(name, { model, … })` renders one layer by hand with typed `props` and a
  `Wrapper`, for asserting on actions with `vi.fn()` (see [Loading and failure](#loading-and-failure)).
- `createTestServer`, `createTestServerCard` and the file fixtures build models and API objects.

<!-- sample: aurora/src/theme.spec.tsx#tests -->
```tsx
test('lays the dashboard out as cards with power buttons beside their links', () => {
    rendered = renderView('dashboard.serverList', { theme, fixture: 'servers' });

    const survival = screen.getByRole('link', { name: 'Survival' });

    expect(survival.getAttribute('href')).toBe('/server/a1b2c3d4');
    expect(survival.closest('li')?.textContent).toContain('Restart');
    expect(screen.getByRole('link', { name: 'Creative' }).closest('li')?.textContent).toContain('Start');
});

test('puts the power controls in the server header and asks before a kill', () => {
    rendered = renderView('layout.server', { theme, fixture: 'owner' });

    const header = screen.getByRole('heading', { name: 'Survival' }).closest('header');

    expect(header?.textContent).toContain('Restart');

    rendered = renderView('server.power', { theme, fixture: 'stopping' });
    expect(screen.getByRole('button', { name: 'Kill' }).hasAttribute('disabled')).toBe(false);
});

test('keeps the engine field attributes on the sign-in form', () => {
    rendered = renderView('auth.loginForm', { theme, fixture: 'invalid' });

    const password = screen.getByLabelText('Password');

    expect(password.getAttribute('autocomplete')).toBe('current-password');
    expect(password.getAttribute('aria-invalid')).toBe('true');
    expect(screen.getByText('Please enter your account password.')).not.toBeNull();
});

test('draws buttons as pills and keeps their state attributes', () => {
    rendered = renderPrimitive('Button', { children: 'Save', color: 'red' }, { theme });

    const button = rendered.container.querySelector('button');

    expect(button?.className).toContain('rounded-full');
    expect(button?.dataset).toMatchObject({ ui: 'button', part: 'root', color: 'red', size: 'small' });
});
```
<!-- /sample -->

### Extending another theme

`theme.extends` builds on another installed theme. The chain runs classic, then the parent (and its
parents), then the child:

- **Views and skins** stack in that order. The child's `Default` renders the parent's layer.
- **Settings** are the union of the chain's; a child's setting of the same name replaces the
  parent's, which is how a child changes a default.
- **Schemes** are the child's when it declares them, else the nearest parent's.
- **Stylesheet**: the panel links one theme stylesheet, the nearest one in the chain. A child
  without `theme.stylesheet` uses its parent's. A child with one replaces the parent's whole, so
  build on it by importing the parent's source CSS into yours.
- The parent has to be installed and enabled before the child can be enabled, and cannot be
  disabled while the child is. A missing parent or a cycle stops the child from being activated.
  `requires.extensions` pins the parent's version.

<!-- sample: child-theme/extension.json -->
```json
{
    "id": "aurora-sunset",
    "type": "theme",
    "name": "Aurora Sunset",
    "version": "1.0.0",
    "requires": { "panel": "^2.0.0-dev", "sdk": ">=2.0.0-beta.5 <3.0", "extensions": { "aurora": "^1.0" } },
    "ui": { "entry": "dist/client.js" },
    "theme": {
        "extends": "aurora",
        "settings": {
            "accent": { "type": "color", "label": "Accent", "default": "#f97316", "token": "--aurora-accent" }
        }
    }
}
```
<!-- /sample -->

<!-- sample: child-theme/src/theme.tsx -->
```tsx
import { defineTheme, type ViewProps } from '@pterodactyl/sdk/theme';

/** Aurora's server card is the layer below, so `Default` renders it. */
function SunsetCard({ Default }: ViewProps<'dashboard.serverCard'>) {
    return (
        <div className='border-l-2 border-primary pl-3'>
            <Default />
        </div>
    );
}

export default defineTheme({ views: { 'dashboard.serverCard': SunsetCard } });
```
<!-- /sample -->

### Activation, preview, safe mode and fallback

- **Activate** on **Admin > Themes** or with `php artisan p:theme:activate <id>`;
  `p:theme:reset` returns to classic and `p:theme:list` shows every theme. The active theme cannot be
  disabled or removed.
- **Preview for me** uses a theme in the current admin's session only, until they stop it.
- **Safe mode**: opening any page with `?safe_mode=1` renders that session with classic and no
  extension bundles; `?safe_mode=0` leaves it. The Themes page links to both. Use it when a theme or
  an extension makes the panel unusable.
- **Boot**: the panel loads the theme chain's entries in parallel before its first render, for at
  most 2.5 seconds. When an entry fails to load, does not export a `defineTheme` result, or takes
  longer, that browser shows classic, logs the error, and shows root admins a notice with it.
- **Layers**: a view layer that throws is reported against its package in the browser console, and
  the layer below renders in its place. The inspector marks the failed layer.
- **Compatibility**: an active theme whose `requires` no longer match, after a panel or SDK update,
  is marked failed on the Extensions page and every page renders classic until it is updated.

### Versions and compatibility

Declare `requires.panel` and `requires.sdk` (`>=2.0.0-beta.5 <3.0`) in the manifest. During the 2.0
betas, the view names, models, parts and fixtures, and the primitives' parts and state, can change
in any beta: build against the SDK of the panel you target, run `npm run typecheck` and
`npm run doctor` after updating it, and rebuild so `dist/theme.json` records the panel's core
classes. The type checker catches a model field that changed; `doctor` catches an overridden view
that no longer exists. From 2.0, additions to the contract are minor versions and removals major.
