# Theme fixes from the first dogfood: doc notes

Notes for the THEMES.md and README chapters. Each section says what changed and what the docs should say.

## The app shell knows the area (`layout.app` model `area`)

`AppLayoutModel.area` says where the user is and carries the model of that area's own layout:

```ts
type AppArea =
    | { kind: 'dashboard' } // the dashboard and any page outside the areas below
    | ({ kind: 'account' } & AccountLayoutModel) // { nav }
    | ({ kind: 'server' } & ServerLayoutModel) // { name, identifier, uuid, internalId, state, conflict, adminLink, nav, server }
    | ({ kind: 'admin' } & AdminLayoutModel); // { nav, user }
```

- `nav.items` are the same items the area's layout gets: filtered by subuser permissions and egg rules.
  Render them with `<Navigation items={area.nav.items}>`, which applies extension screen gates and badges.
- A sidebar theme draws every navigation in `layout.app` and overrides `layout.server`, `layout.account`
  and `layout.admin` with thin layouts, e.g. `({ children }) => <Main>{children}</Main>`.
  Render the area's slots (`server.navigation.before/after` with `data={area.server}`,
  `account.navigation.*`, `panel.navigation.*`) where the shell draws that navigation. The dev-mode
  "renders none of its slots" check counts slots a view's enclosing views render, so a thin layout does
  not warn when the shell renders its slots.
- Fixtures: `layout.app` has `accountArea`, `serverArea` and `adminArea` besides the dashboard ones
  (`user`, `admin`, `schemes`, `logo`).

Recipe (sidebar part):

```tsx
function AreaNav({ area }: { area: AppArea }) {
    if (area.kind === 'dashboard') return null;
    const items = area.kind === 'server' && area.adminLink ? [...area.nav.items, area.adminLink] : area.nav.items;

    return (
        <nav aria-label={area.kind === 'server' ? area.name : 'Section'}>
            {area.kind === 'server' && <Badge>{area.state ?? 'unknown'}</Badge>}
            <Navigation items={items}>
                {(item, { badge }) => (
                    <NavigationLink item={item}>
                        {item.icon && <NamedIcon name={item.icon} size={16} aria-hidden />}
                        {item.label}
                        {badge}
                    </NavigationLink>
                )}
            </Navigation>
        </nav>
    );
}
```

## Server power state on `layout.server` (`state`, `uuid`)

`ServerLayoutModel` gained `uuid` and `state: ServerRunState | null` (`'offline' | 'starting' | 'running' |
'stopping'`, as `server.power` and `server.stats` name it). It is the cached resources query, fetched once
when the server opens and kept live by the server's websocket, which now writes every `status` event into
that cache (so dashboard cards returning from a server show the latest state too). Fixtures: `owner`,
`subuser`, `admin` are `running`; new `offline`; `installing` and `suspended` are `offline`.

## `<Main>`

- Exactly one `<main>` renders, from the outermost `<Main>`; nested ones render their children in place.
- A nested `<Main className="…">` now keeps its classes on a `<div>` around its children instead of dropping
  them. A nested `<Main>` without a class adds no element.

## Failing layers

- A theme or extension layer that throws falls back to the layer below once. If a layer below the failed
  one throws too (classic included), the panel's standard error notice renders for that view, with a
  Retry that tries the failed layer again. Nothing re-renders in a loop (before, an effect throwing in
  classic under a failed theme layer re-mounted forever, e.g. `server.graphs` without `ResizeObserver`).
- Each failure logs one `console.error`: `[extensions] "<layer id>" failed in view "<view>": <error>`.
- The inspector is gone (see below); the console line is how to spot a failing layer.

## Strict test renderers

- `renderView(name, options)` throws when the view fails to render, and when any theme or extension
  layer throws: `The "theme" layer of view "server.graphs" failed: <message>` (a `ViewLayerError` with
  `view`, `layer` and `cause`). Nested views count. `{ strict: false }` lets layers fall back as in the
  panel. Failures after the first render (say on a click) are thrown by `unmount()`.
- `createViewTestHost(name, options)` is strict too: the layer under test or a nested layer that throws
  fails the render (the error propagates out of RTL's `render`/`act`). `{ strict: false }` opts out.
- `ViewLayerError` is exported from `@pterodactyl/sdk/testing`.
- `renderView(name, { slots: { 'server.navigation.before': () => <span>probe</span> } })` mounts
  components in slots as an extension's `slots.register` would, typed per slot; they are removed on
  unmount. Use it to test that a view renders the slots it replaces.
- The SDK's Vitest setup (`pterodactylTestSetup`) now provides `ResizeObserver`, `IntersectionObserver`
  (inert) and `matchMedia` (matches nothing) besides `PointerEvent` and `scrollTo`. Theme tests need no
  stubs; `vi.stubGlobal` still replaces them where a test drives them.
- `usePanelLocation()` works in views rendered from fixtures (tests): outside a route it returns the
  path with empty `params` and `search` instead of throwing.

## Navigation details

- Every core nav item has an `icon` (lucide name for `NamedIcon`):
  - account: Account `user-round`, API Credentials `key-round`, SSH Keys `terminal`, Activity `history`;
  - server: Console `square-terminal`, Files `folder`, Databases `database`, Schedules `calendar-clock`,
    Users `users`, Backups `archive`, Network `network`, Startup `rocket`, Settings `settings`,
    Activity `history`; the admin link stays `external-link`;
  - admin: Activity is now `history` (the rest had icons already).
  - `AppLayoutLink.icon`: dashboard `layers`, admin `user-cog`, account `user-round`.
  Classic still shows its account and server tabs as text (extension screens keep their icons), through
  `views/layout/tabLabel.tsx`, which `eject` copies with those layouts; its admin sidebar now shows the
  history icon for Activity instead of the fallback puzzle.
- `DropdownMenu.Item` takes `destination` (a `PanelDestination`): the item renders as a link (`<a href>`,
  `role="menuitem"`, same skin parts) that navigates and closes the menu.
  `<DropdownMenu.Item destination={{ to: '/account' }}>Account</DropdownMenu.Item>`
- Closing a drawer on navigation: an effect on the path, which also works in tests.

  ```tsx
  const { pathname } = usePanelLocation();
  useEffect(() => setOpen(false), [pathname]);
  ```

  No `useOnNavigate` hook was added; this is two lines and needs no new concept.

## Layout spacing tokens

- `--layout-gutter` (default `calc(var(--spacing) * 4)`): page content's side margin until it centres.
- `--layout-content-width` (existing): its maximum width (`max-w-panel`).
- `page-width` utility: the geometry `PageContentBlock` and `ServerContentBlock` use (max width
  `--layout-content-width`, `--layout-gutter` at the sides, centred from `xl`). Put it on a layout's own
  header to line up with the pages below: `<header className="page-width mt-4 sm:mt-6">`.
  Core renders exactly as before.

## Palette

`text-white`, `bg-black/15` etc. work in theme builds again: `--color-white` and `--color-black` are
defined after the palette reset. `transparent`, `current` and `inherit` always worked. The rest of
Tailwind's default palette stays removed; add colours in `@theme` as before. Core's compiled CSS is
unchanged.

## Packaging

- `defineThemeConfig({ …, sourcemap })`: off by default for themes, because a map embeds the sources.
  `sourcemap: true` turns it on (e.g. for a private build). Extensions keep `sourcemap: true`.
- `p:extension:pack` leaves `*.map` files out of the archive unless given `--sourcemaps`.
- The theme scaffold's `npm run pack` passes `--force`, so re-packing replaces the archive, and its
  `.gitignore` (shared with extensions) ignores `*.pteroext`.

## Scaffold paths

- `p:extension:make <id> --path=<dir>` creates the package in exactly `<dir>`.
- `--out=<dir>` (unchanged) is the parent: the package goes in `<dir>/<id>`. `--help` says so.
- Passing both fails before writing anything.

## Removed: the theme workbench

`/panel/theme-workbench`, `page.admin.themeWorkbench`, the workbench components, the UI-kit gallery
(`themes/gallery.tsx`), preview frames and the portal-container context that sent overlays into them,
and the Workbench button on the Themes page are gone. View fixtures, `renderView`, `renderPrimitive`
(same primitive names), `createViewTestHost` and `preloadTheme` remain; tests are the way to render views
without a backend. Docs should drop the workbench section, its URL parameters (`view`, `fixture`, `scheme`,
`width`, `ui`) and the scaffold README's mention (done in the stub). `Portal` renders into
`#modal-portal`, or the body where there is none (as in tests), so `createViewTestHost` no longer adds a
`#modal-portal` for the file manager. Dialogs, menus, selects and tooltips portal into the body again.
The planned `useViewDocument()` hook and the `width=` URL parameter fix were dropped with the workbench.

## Removed: the inspector

The Alt-click inspector, its floating button, the `data-view` wrappers, the view probes
(`ViewInspectionContext`), `?inspect=1` and `SiteConfiguration.theme.inspect` (server payload and client
type) are gone. Failing layers log a `console.error` naming the view and the layer instead.
