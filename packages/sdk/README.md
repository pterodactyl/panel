# @pterodactyl/sdk

Build extensions for the Pterodactyl panel (v2+). This package provides:

- **Types** for the runtime API — the actual implementation is provided *by the panel*
  through its import map, so your built extension never bundles the SDK, React, or
  TanStack Query: it shares the panel's own instances.
- **The Vite build preset** (`@pterodactyl/sdk/vite`) that keeps those modules external
  and emits `dist/client.js` plus lazy screen chunks.

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

`vite build` → `dist/client.js`, referenced from your `extension.json`:

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

The panel renders before optional bundles finish importing. Each extension commits atomically when ready; slot display order follows the configured extension order. Screens show local loading state while their bundle or chunk loads. Slots have individual Suspense and error boundaries, and SDK websocket callbacks are isolated and attributed to their mod. Native extensions still execute in the panel's JavaScript environment; boundaries are not a sandbox and cannot interrupt synchronous code.

Recoverable render/query failures offer Retry. Failed screen chunks offer an explicit page reload because resetting an error boundary cannot evict a rejected browser module. Enabling, disabling, updating, or changing frontend configuration takes effect on the next full page load; reload after management changes. Already evaluated JavaScript is not unloaded dynamically.

## Types and shared dependencies

The panel currently supports the React 19.3 client API. Server-only caching APIs, development-only `captureOwnerStack`, and unstable cache-refresh APIs are not part of the shared React facade. React, React DOM, JSX runtime, Query, and SDK entry points stay external through the Vite preset.

SDK declarations are generated from the runtime public surface with `npm run sdk:generate` at the panel root. `npm run sdk:check` checks reproducibility and compiles a packed-package consumer, including negative type cases. Component props, form fields, and `useCurrentServer(server => server.attributes.name)` preserve their actual types. SDK peer dependencies include the existing libraries referenced by those declarations; use SDK form hooks/components to share the panel's bound form contexts.

Declare screen metadata in the manifest and register each implementation by ID with `screens.register`. Build extensions against the SDK version used by the panel.

A `file:` dependency on `packages/sdk` is a link into the panel checkout. TypeScript follows links to their real location by default, so the SDK declarations would take React, TanStack Query and the other peers from the panel's `node_modules` while your code takes them from yours; when the versions differ, `useQuery(serverFileContentQueryOptions(...))` fails on two unrelated `QueryClient` types. Scaffolded extensions set `"preserveSymlinks": true` in `tsconfig.json`, which resolves the SDK's peers from the extension, the same way the Vitest preset does at runtime. Add that option to an extension scaffolded earlier and remove any `paths` entries for `react`, `@tanstack/react-query` or `lucide-react`. An SDK installed from a tarball or registry needs neither.

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
submission, and persistence. Keep values in that form rather than copying a separate draft. `columns.register('admin.nodes', { id, label,
component })` adds a typed cell to the native table; servers and eggs are also supported.
Columns commit atomically and render inside local error boundaries. Table, Tooltip,
DropdownMenu, and Pagination complement the existing form and dialog primitives. `Empty`
(with `EmptyHeader`, `EmptyMedia`, `EmptyTitle`, `EmptyDescription`, `EmptyContent`)
matches the panel's empty states; pass it as a `Table`'s `emptyState`.

`useExtensionTranslation('messages')` loads the provider's registered
`resources/lang/<locale>/messages.php` group. Extension CSS is built with the extension's own
Tailwind prefix (see [Styling](#styling)). The Vite preset waits for entry CSS before setup.

Themes override the panel's CSS tokens: colour roles, the `--terminal-*`
and `--editor-*` palettes, `--selection`, `--scrollbar-*`, `--color-scheme`, `--theme-color`,
and the layout tokens `--layout-content-width`, `--layout-auth-width`,
`--layout-sidebar-width` (`max-w-panel`, `max-w-auth`, `w-sidebar`) and `--spacing` (density).
Extension utilities read the same live tokens (`hw:bg-card`, `hw:w-sidebar`, `hw:p-4` for the prefix `hw`).
`document.documentElement.dataset.subNavigation = 'side'` moves the account and server
sub-navigation into a sidebar. The console and the `theme-color` meta re-read their tokens
when a stylesheet or an inline property on `<html>` changes; `onThemeChange(listener)` gives
canvas or chart code the same signal, `readThemeToken('--name')` reads a value, and
`notifyThemeChange()` reports changes made through the CSSOM. Only the panel's own
`theme-color` tag follows the token; one registered with `registerHeadTags` is left as rendered.

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

## Backend provider API

The manifest's `provider` class is loaded through its `autoload` map of PSR-4 prefixes to
package directories, such as `{ "Acme\\Billing\\": "src" }`. A prefix cannot equal, contain
or sit inside `Pterodactyl\`, `Illuminate\`, `Laravel\`, `Symfony\` or a namespace of the
panel's Composer packages, and enabling fails while another enabled extension autoloads an
overlapping prefix. Extension class loaders are registered behind the panel's, so once an
extension's `vendor/autoload.php` has returned, a class the panel or its packages provide
always comes from the panel.

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
`Pterodactyl\Contracts\` that the panel binds. The decorator receives the implementation that
would otherwise be used and returns an implementation of the same contract:

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

The wrapper owns the call: it decides whether and when to invoke the inner action and
must forward fluent options such as `withForce()` itself, returning itself. Whatever it
throws reaches the caller unchanged, and exceptions of the inner action pass through
untouched. A reported exception raised from the extension's package is recorded against
the extension. The decorator is skipped while the extension is disabled or failed to boot;
one that throws or returns another type is recorded and skipped, so the core action always
resolves. Extensions wrap in load order, the last one outermost. The actions that install,
enable, disable, remove and configure extensions (`Contracts\Extensions\*`) and apply themes
(`Contracts\Themes\*`) cannot be wrapped; asking for one fails the provider's boot.

Resolve the same contracts from the container to call core behaviour instead of
reimplementing it. Beside the mutating actions, these read or complete on your behalf:

| Contract | Call |
| --- | --- |
| `Contracts\Files\ReadsFileContents` | `read($server, $path, ?int $maxBytes = null): string`, bounded by the panel's editable file size unless a limit is given. |
| `Contracts\Files\ListsDirectories` | `list($server, $directory = '/')`: the directory's `DaemonFileObject` entries. |
| `Contracts\Servers\ReadsServerLogs` | `read($server, $lines = 100)`: recent console lines, oldest first, at most `MAX_LINES`. |
| `Contracts\Servers\ReadsServerState` | `read($server): ServerState`, live state and usage from the cache the client API shares (up to 20 seconds old). |
| `Contracts\Servers\ChangesServerEgg` | `change($server, $egg, keepVariables: false)`: resets startup, image and variables to the new egg, optionally carrying over values whose names and rules still match. |
| `Contracts\Users\CompletesLogins` | `complete($user)` after you verified a first factor (it issues the two-factor checkpoint when the account needs one), `establish($user)` once every factor has passed, and `pendingCheckpoint()`. |

These actions do not check permissions; authorize the caller first, as the core
controllers do. They can be wrapped like any other action.

### Commands and scheduled tasks

`registerCommands([CleanLogsCommand::class])` adds artisan commands and
`registerSchedule(fn (Schedule $schedule) => $schedule->command('logs:clean')->daily())`
defines tasks on the panel's scheduler. Both exist only while the extension is enabled and
booted successfully. Prefix command names with the extension id. A scheduled task that
fails, or a schedule callback that throws, is recorded against the extension; a callback
that throws schedules nothing.

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

Classes passed to SDK components and to `Default` in a component replacement are merged with
the component's own for the prefix of every enabled extension: `<Button className="hw:mt-4">`
drops the button's `mt-*` default, per variant, exactly as a panel class would
(`hw:hover:bg-accent` replaces `hover:bg-primary/90`; `hw:bg-accent` alone does not). In tests
pass the prefix to the host: `createComponentTestHost(name, { model, prefix: 'hw' })`.

`p:extension:doctor`, `p:extension:pack`, install and enable all reject a build whose CSS has
a rule in `@layer utilities` that is not built on a `.hw\:` class, or a custom property in
`@layer theme` that does not start with `--hw-`, for the prefix that extension declares. A
build with either layer and no `ui.prefix` is rejected with the line to add.

## Component replacements

Declare supported component names in `extension.json` under `ui.components`, with a `requires.sdk` constraint of `>=2.0.0-beta.4 <3.0`. Enabling the extension activates its replacements. Enable and upgrade operations reject claims already owned by another enabled extension.

```tsx
import { definePterodactylExtension, type ReplacementProps } from '@pterodactyl/sdk';

function ServerCard({ Default }: ReplacementProps<'dashboard.serverCard'>) {
    return <Default className="hw:gap-3" />;
}

export default definePterodactylExtension({
    setup({ components }) {
        components.replace('dashboard.serverCard', ServerCard);
        components.replace('server.files.details', { load: () => import('./FileDetails') });
    },
});
```

`packages/hello-world` replaces the server card, the file details and the file editor, each with a test.

`ReplacementProps<Name>` supplies a read-only `model`, native `Default`, and typed `parts`. `Default` accepts `className` and partial part overrides. Parts receive `{ model }`, typed by `ComponentPartProps<Name>`; define them outside the replacement's render function.

| Name | Model | Parts |
| --- | --- | --- |
| `dashboard.serverCard` | `ServerCardModel` | `identity`, `address`, `metrics` |
| `server.files.details` | `FileDetailsModel` | `icon`, `name`, `size`, `modified` |
| `server.files.editor` | `FileEditorModel` | `notice`, `editor`, `language`, `actions` |
| `server.files.manager` | `FileManagerModel` | `toolbar`, `list`, `selection` |

The server card and file details contain presentation inside core links. Keep interactive controls in action slots. The panel retains queries, permissions, navigation, accessible link names, selection, and menus. The native server-card identity part includes the `dashboard.serverRow.name.after` slot.

### File editor

`server.files.editor` replaces the editing surface of `/server/$id/files/edit` and `/files/new`. The panel keeps the route, breadcrumbs, loading and error screens, the new-file name dialog, the write mutation, permission checks and the unsaved-changes guard. The replacement mounts once per opened document, after its content has loaded.

| Model field | |
| --- | --- |
| `path`, `name`, `isNew` | The open file. For a new file `path` is its directory and `name` is empty. |
| `content` | Text to open: the loaded file, or the restored draft of a new file. It does not change while the document is open. |
| `language` | Syntax hint as a MIME type (`text/x-yaml`); the native `language` part changes it. |
| `readOnly` | The user lacks `file.update` (or `file.create` for a new file). |
| `dirty`, `saving` | Derived by the panel from the reported buffer and the running write. |
| `change(content)` | Report the buffer after every edit. The panel saves exactly this text. |
| `save()` | Write the buffer in place; a new file first asks for its name in the panel's dialog. Resolves `false` when read-only, cancelled or failed. |
| `saveAs(name)` | Write the buffer to a name beside the file, or an absolute path, and open it. Needs `file.create`. |

```tsx
import { useExtensionAction, type ComponentPartProps, type ReplacementProps } from '@pterodactyl/sdk';

function Buffer({ model }: ComponentPartProps<'server.files.editor'>) {
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
export default function Editor({ Default }: ReplacementProps<'server.files.editor'>) {
    return <Default parts={{ editor: Buffer }} />;
}
```

Because the panel holds the buffer, a replaced `editor` part works with the native Save button, and the native editor continues with the user's text if the replacement fails to render. Do not add your own `useNavigationBlocker` here.

### File browser

`server.files.manager` replaces the browser on `/server/$id/files`: toolbar, listing and selection bar. The panel keeps the page, the directory query, the selection store, uploads, and every mutation and navigation. `server.files.before` and `server.files.after` stay around the replacement; the `server.files.toolbar`, `rowActions` and `selectionActions` slots and the `server.files.details` replacement belong to the native parts and render only where you keep those parts.

`model.entries` are `FileManagerEntry` rows of `model.directory` (directories first, at most 250, `truncated` when there are more) with `name`, `path`, `kind`, `size`, `mimetype`, `mode`, `modeBits`, `modifiedAt` and `openable`. `loading` is true until a directory is first listed, `refreshing` during later fetches. `selection` holds the selected names and `permissions` says which of `create`, `update`, `delete` and `archive` the user has.

`model.actions` take names in the current directory: `open(name)` enters a directory or opens a text file in the editor, `navigate(directory)`, `newFile()`, `select(names)`, `refresh()`, `createDirectory(name)`, `rename([{ from, to }])` (a relative path in `to` moves), `remove(names)`, `copy(name)`, `archive(names)`, `extract(name)`, `chmod([{ file, mode }])`, `download(name)` and `upload(files)`. They run the panel's own mutations, with its cache updates and notifications. A call the user has no permission for rejects before any request; the API enforces the same rule. `remove` deletes at once, so confirm first. For files outside the current directory, such as a tree view, use `serverFilesQueryOptions` and the mutation hooks.

```tsx
export default function Browser({ model, parts }: ReplacementProps<'server.files.manager'>) {
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

Rows share one loading decision per page and component. The five-second deadline, import failures, and render failures restore the native view. Late imports cannot replace a native view committed after a timeout. Core state remains outside the presentation boundary. Use local Suspense for extension-owned asynchronous rendering and `useExtensionAction` for callbacks.

The testing entry exports `createComponentTestHost(name, { model, extensionId?, prefix? })`, returning `Wrapper`, typed `props` and `dispose`, plus the model fixtures `createTestServerCard`, `createTestFileDetails`, `createTestFileEditor`, `createTestFileManager` and `createTestFileManagerEntry`. This exercises the panel's native defaults and parts without issuing core queries. Pass `vi.fn()` callbacks in the fixture to assert on `save`, `change` or an action. The file browser host mounts a server, router and store for the native rows; call `dispose()` after unmounting.
