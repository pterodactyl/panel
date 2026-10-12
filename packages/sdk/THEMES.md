# Themes

A theme changes how the Pterodactyl panel looks. This guide goes from shallow to deep: read until you
have what you need, then stop. Most themes never get past level 2.

1. [How themes work](#how-themes-work)
2. [Level 1: colours, fonts and spacing](#level-1-colours-fonts-and-spacing)
3. [Level 2: restyle components](#level-2-restyle-components)
4. [Level 3: change the layout](#level-3-change-the-layout)
5. [Level 4: rebuild pages](#level-4-rebuild-pages)
6. [Settings, schemes and branding](#settings-schemes-and-branding)
7. [Tools](#tools)
8. [Rules and guarantees](#rules-and-guarantees)
9. [Reference](#reference): every token, primitive and view
10. [Glossary](#glossary)

## How themes work

The panel is an **engine** and a **theme**. The engine owns data, permissions, routing, requests,
realtime updates and accessibility. The theme owns every pixel. The stock look, **classic**, is a theme
written on the same public API you use, so anything classic draws, your theme can draw differently. A
theme is an extension with `"type": "theme"`, so it can also have a PHP provider, screens and slots.

A theme has four tools:

| Tool | Changes | Written in |
| --- | --- | --- |
| Stylesheet and tokens | Colours, fonts, spacing, radius, any CSS | `src/theme.css` |
| Skins | The classes of the UI kit: buttons, cards, inputs, dialogs | `defineTheme({ ui })` |
| Views | The markup of a layout, a page, or a block of a page | `defineTheme({ views })` |
| Slots | Nothing: they are where extensions add content, and views keep them | `<Slot name="…" />` |

Skins and views stack in **layers**: classic at the bottom, then the theme yours extends, then yours,
then extensions. Each view layer receives `Default`, a component that renders the layer below it:

```
extensions       the top layer renders
    │ <Default />
your theme       defineTheme({ views })
    │ <Default />
parent themes    "theme.extends" in the manifest
    │ <Default />
classic          the stock panel
```

Render `<Default />` to keep what is below and add to it; leave it out to replace it. A layer that
throws is reported and the layer below renders instead. Whatever you do not override stays classic.

| I want to … | Go to |
| --- | --- |
| Change colours, fonts, corner radius or spacing | [Level 1](#level-1-colours-fonts-and-spacing) |
| Offer a dark and a light scheme | [Level 1](#dark-and-light) |
| Restyle every button, card or dialog | [Level 2](#level-2-restyle-components) |
| Adjust one component with plain CSS | [Level 2](#plain-css-with-data-ui) |
| Move the navigation into a sidebar | [Level 3](#level-3-change-the-layout) |
| Rearrange a page, or move the power buttons into the header | [Level 4](#level-4-rebuild-pages) |
| Find the view that draws something | [Finding the right view](#finding-the-right-view) |
| Let administrators pick a colour, upload a logo or toggle a layout | [Settings](#settings-schemes-and-branding) |
| Look up a token, primitive or view | [Reference](#reference) |

## Level 1: colours, fonts and spacing

From the panel's root directory, five commands scaffold a theme, build and install it, activate it,
and rebuild it on every change:

```sh
php artisan p:extension:make sunrise --theme
cd extensions/sunrise && npm install
php ../../artisan p:extension:dev . --enable
php ../../artisan p:theme:activate sunrise
php ../../artisan p:extension:dev . --watch
```

While the last one runs on a panel with `APP_DEBUG=true`, a stylesheet change swaps into open pages
without a reload, and any other change reloads them. To try a theme without switching everyone to it,
skip `p:theme:activate` and choose **Preview for me** on **Admin > Themes**.

At this level you edit only `src/theme.css`. It starts with `@import '@pterodactyl/sdk/theme-entry.css';`,
which is the panel's whole stylesheet: Tailwind, the tokens, and every class the panel uses. The build
replaces the panel's stylesheet, and your rules come after the panel's, so they win. Write plain
Tailwind with no prefix.

### Tokens

A **token** is a CSS custom property the panel's classes read. Set one and everything that reads it
changes: `--primary` colours primary buttons and selected items, `--radius` sets every rounded corner,
and `--spacing` scales every padding, margin and gap. [Tokens](#tokens) lists them all.

The colour classes are roles such as `bg-card` and `text-muted-foreground`; Tailwind's palette is not
there. Add a colour of your own in `@theme`: `--color-brand: oklch(0.7 0.2 300);` gives `bg-brand`.

### Dark and light

`<html>` has the class `dark` or `light`. Put each scheme's colours in `.dark { }` and `.light { }`,
and values for both in `:root`. The manifest's `theme.schemes` lists the schemes the theme offers, the
default first. Classic offers dark only and its light palette is provisional, so a theme that offers
light sets its own light colours.

### Fonts

Ship font files with the theme and reference them relative to the stylesheet; the build copies them
beside `dist/theme.css`. Set `--font-sans` (body) and `--font-header` (headings) in `@theme`. Fonts from
another origin, such as a CDN, are refused.

The build warns that `/assets/fonts/ibm-plex-sans-latin-wght-normal.woff2` did not resolve. That is the
panel's own font, served by the panel, and the warning is expected.

### A complete level 1 theme

<!-- sample: level1-css/src/theme.css -->
```css
/* The panel stylesheet with every class the panel uses. Everything after it is this theme's. */
@import '@pterodactyl/sdk/theme-entry.css';

/* A font shipped with the theme: the build copies the file beside dist/theme.css. */
@font-face {
    font-family: 'Sunrise Sans';
    font-weight: 100 900;
    font-display: swap;
    src: url('./fonts/sunrise-sans.woff2') format('woff2');
}

@theme {
    --font-sans: 'Sunrise Sans', system-ui, sans-serif;
    --font-header: 'Sunrise Sans', system-ui, sans-serif;
}

/* Both schemes. Every p-*, m-*, gap-* and w-* class multiplies --spacing (Tailwind's is 0.25rem). */
:root {
    --radius: 0.75rem;
    --spacing: 0.225rem;
    --layout-content-width: 80rem;
}

.dark {
    --background: oklch(0.19 0.02 45);
    --foreground: oklch(0.94 0.01 70);
    --card: oklch(0.24 0.025 45);
    --card-foreground: var(--foreground);
    --popover: oklch(0.28 0.03 45);
    --popover-foreground: var(--foreground);
    --muted-foreground: oklch(0.72 0.02 60);
    --border: oklch(0.34 0.03 45);
    --primary: oklch(0.74 0.16 55);
    --primary-foreground: oklch(0.2 0.03 45);
}

.light {
    --background: oklch(0.98 0.01 80);
    --foreground: oklch(0.24 0.03 45);
    --card: oklch(1 0 0);
    --card-foreground: var(--foreground);
    --popover: oklch(0.96 0.015 80);
    --popover-foreground: var(--foreground);
    --muted-foreground: oklch(0.5 0.02 60);
    --border: oklch(0.9 0.015 80);
    --primary: oklch(0.62 0.17 50);
    --primary-foreground: oklch(0.99 0 0);
}
```
<!-- /sample -->

<!-- sample: level1-css/extension.json -->
```json
{
    "$schema": "./node_modules/@pterodactyl/sdk/manifest.schema.json",
    "id": "sunrise",
    "type": "theme",
    "name": "Sunrise",
    "version": "1.0.0",
    "requires": { "panel": "^2.0.0-dev", "sdk": ">=2.0.0-beta.5 <3.0" },
    "theme": {
        "stylesheet": "dist/theme.css",
        "schemes": ["dark", "light"]
    }
}
```
<!-- /sample -->

This theme has no JavaScript: its manifest has no `ui` and its `vite.config.mjs` no `entry`. The
scaffold also writes `src/theme.tsx`, with a skin and a layout layer, for the later levels; the build
ignores it once those two lines are gone. [`docs-samples/level1-css`](docs-samples/level1-css) in the
panel repository holds the files.

You now have a theme.

## Level 2: restyle components

The panel draws its interface with a UI kit of 28 **primitives**, such as `Button`, `Card` and `Dialog`.
Extensions use them too, so restyling a primitive restyles it everywhere.

Each primitive has named **parts** (a `Card` has `root`, `header`, `title`, `icon`, `actions` and
`body`) and **state** (a `Button` has `color`, `size`, `secondary` and `loading`). A **skin** gives
parts a **recipe**: the classes the part gets in a given state. `src/theme.tsx` maps primitives to skins
with `defineTheme({ ui: skins })`:

<!-- sample: level2-skins/src/skins.tsx#skins -->
```tsx
export const skins = {
    Tooltip: tooltip,
    Badge: badge,
    Button: button,
    Card: card,
    Alert: Banner,
} satisfies ThemeSkins;
```
<!-- /sample -->

`npx pterodactyl-theme skins Button` prints classic's skin of a primitive, ready to paste and edit.

### Recipes

A recipe is a string, the same classes in every state:

<!-- sample: level2-skins/src/skins.tsx#string -->
```tsx
/** A string: the same classes in every state. It replaces classic's whole recipe, so it keeps z-9999. */
const tooltip = skin('Tooltip', {
    root: 'pointer-events-none z-9999 max-w-96 rounded-full bg-foreground px-3 py-1 text-xs text-background',
});
```
<!-- /sample -->

Or a [class-variance-authority](https://cva.style) object, with `base`, `variants` keyed by state,
`compoundVariants` and `defaultVariants`:

<!-- sample: level2-skins/src/skins.tsx#cva -->
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

Or a function of the state:

<!-- sample: level2-skins/src/skins.tsx#function -->
```tsx
type ButtonState = PrimitiveState<'Button'>;

const fills: Record<ButtonState['color'], string> = {
    primary: 'bg-primary text-primary-foreground hover:not-disabled:bg-primary/85',
    grey: 'bg-popover text-foreground hover:not-disabled:bg-secondary',
    green: 'bg-success text-success-foreground hover:not-disabled:bg-success/85',
    red: 'bg-destructive text-destructive-foreground hover:not-disabled:bg-destructive/85',
    orange: 'bg-caution text-caution-foreground hover:not-disabled:bg-caution/85',
};

/** A function of the state. Only `root` is replaced: `spinner` and `label` keep the recipes below. */
const button = skin('Button', {
    root: ({ color, size, secondary }) =>
        [
            'relative inline-flex items-center justify-center gap-2 rounded-full font-medium transition disabled:opacity-50',
            size === 'xsmall' ? 'h-7 px-3 text-xs' : 'h-9 px-4 text-sm',
            size === 'xlarge' && 'w-full',
            secondary
                ? 'bg-transparent text-foreground ring-1 ring-border hover:not-disabled:bg-popover'
                : fills[color],
        ]
            .filter(Boolean)
            .join(' '),
});
```
<!-- /sample -->

### Replace or extend

A recipe **replaces** the recipe below it for its part, whole. Classes are never merged, so keep the
classes a part needs to work, such as `z-9999` on a tooltip. Parts a skin leaves out keep the recipe
below.

To **extend** instead, pass `skin()` a function of the layers below:

<!-- sample: level2-skins/src/skins.tsx#extend -->
```tsx
/** Builds on the layers below: `below.root(state)` is their classes for the part. */
const card = skin('Card', (below) => ({
    root: (state) => `${below.root(state)} ring-1 ring-border/60`,
}));
```
<!-- /sample -->

A `className` passed where a primitive is used comes after the recipe and wins any conflict:
`<Button className="mt-4">` replaces the recipe's own `mt-*` class.

### Plain CSS with `data-ui`

Every part renders `data-ui` (the primitive's name in kebab case) and `data-part`. The root also
renders a `data-*` attribute per state value: `data-color="red"`, or a bare `data-loading` while a
boolean is true. Primitives built on Base UI add its state, such as `data-checked`. Tailwind reads these
as variants (`data-checked:bg-primary`), and CSS reaches what a recipe does not:

<!-- sample: level2-skins/src/theme.css#hooks -->
```css
/* Inside @layer components, a utility passed at the call site still wins. */
@layer components {
    [data-ui='button'][data-color='red'] {
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    [data-ui='dialog'][data-part='backdrop'] {
        backdrop-filter: blur(4px);
    }
}
```
<!-- /sample -->

### Replacing a primitive

A presentational primitive, marked Replaceable under [Primitives](#primitives), can be replaced by a
component. It receives the same props, typed by `PrimitiveProps<'Name'>`, must not render the primitive
it replaces, and keeps the original's attributes and accessibility. The others, such as `Dialog`,
`Select` and `Switch`, keep their behaviour in the engine and take only skins.

<!-- sample: level2-skins/src/skins.tsx#component -->
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

`useSkin(name, state)` styles an element of your own like a primitive: `useSkin('Button', state)('root')`
returns the props to spread, and `.classes('root')` only the class string.

### Ten primitives to start with

| Primitive | Draws | Parts | Replaceable |
| --- | --- | --- | --- |
| `Button` | Buttons, and links styled as buttons | `root`, `spinner`, `label` | yes |
| `Card` | Content boxes, with an optional header | `root`, `header`, `title`, `icon`, `actions`, `body` | yes |
| `Input` | Text inputs | `root` | no |
| `Select` | Dropdown selects | `trigger`, `popup`, `item` and 11 more | no |
| `Dialog` | Modals and confirmations | `backdrop`, `root`, `title`, `footer` and 13 more | no |
| `Alert` | Notices in a page | `root`, `icon`, `body`, `title`, `content` | yes |
| `Badge` | Status labels | `root` | yes |
| `Empty` | Empty states | `root`, `header`, `media`, `title`, `description`, `content` | yes |
| `Tooltip` | Tooltips | `root`, `arrow` | no |
| `Spinner` | Loading spinners | `root`, `container` | yes |

[Primitives](#primitives) lists all 28 with their state. In development, skinning a primitive or part
that does not exist logs a warning, and a recipe that throws renders no classes.

## Level 3: change the layout

A **view** is a named piece of the interface, rendered by a stack of layers. A **layout** view frames
the pages of an area:

| Layout | Frames | Its model has |
| --- | --- | --- |
| `layout.app` | Every signed-in page | `brand`, `links`, `user`, the scheme, `signOut` |
| `layout.account` | The account pages, inside `layout.app` | `nav` |
| `layout.server` | A server's pages, inside `layout.app` | `name`, `nav`, `conflict`, `server` |
| `layout.admin` | The admin area, inside `layout.app` | `nav`, `user` |
| `layout.auth` | The sign-in pages | `brand` |

A view's component receives its **model**, the data and actions the engine produces for it, with
`Default`, `className` and `children`. A layout's `children` is the routed page. `ViewProps<'name'>`
types these props. Register views by name:

<!-- sample: level3-sidebar/src/theme.tsx -->
```tsx
import { defineTheme, lazyView } from '@pterodactyl/sdk/theme';

export default defineTheme({
    views: {
        'layout.app': lazyView(() => import('./views/Shell')),
        'layout.account': lazyView(() => import('./views/AccountLayout')),
    },
});
```
<!-- /sample -->

`lazyView` loads a view's code the first time it renders (see [Level 4](#loading-with-lazyview)).

### `<Main>`

A layout places `children` inside `<Main>`, which renders `<main id="main" tabIndex={-1}>`: the target of
the skip link, which the engine focuses after each navigation while it announces the new page title.
Layouts nest, and only the outermost `<Main>` renders the element. In development, a layout that never
renders `<Main>` logs a warning.

### Navigation and the brand

`layout.app` has `model.links` (the dashboard, the admin area for root admins, and the account) and
`model.brand`, the panel's `name` and a `logoUrl` (see [Branding](#branding)).

The other layouts have `model.nav.items`: the area's `NavItem`s, filtered by subuser permissions and egg
rules, extension screens included. `<Navigation>` calls its child with each item and `{ active, badge }`,
and keeps an extension screen hidden until its runtime condition allows it. `NavigationLink` links to
an item and carries `data-status="active"` while the item is active.

<!-- sample: level3-sidebar/src/views/AccountLayout.tsx#account -->
```tsx
/** The account pages with a vertical menu. `model.nav` lists them, extension screens included. */
export default function AccountLayout({ model, children }: ViewProps<'layout.account'>) {
    return (
        <div className='mx-auto flex max-w-panel gap-8 p-6'>
            <nav aria-label='Account' className='w-56 shrink-0 space-y-1'>
                <Slot name='account.navigation.before' />
                <Navigation items={model.nav.items}>
                    {(item, { active, badge }) => (
                        <NavigationLink
                            item={item}
                            className={cn('flex gap-2 rounded-lg px-3 py-2', active && 'bg-muted')}
                        >
                            {item.icon && <NamedIcon name={item.icon} size={16} aria-hidden />}
                            <span className='flex-1'>{item.label}</span>
                            {badge}
                        </NavigationLink>
                    )}
                </Navigation>
                <Slot name='account.navigation.after' />
            </nav>
            <Main className='min-w-0 flex-1'>{children}</Main>
        </div>
    );
}
```
<!-- /sample -->

`useNavigation('account' | 'server' | 'admin')` returns an area's items outside its layout. Classic's tab
bar, which folds items that do not fit into a menu, is `SubNavigation` with `SubNavigationLayout` from
`@pterodactyl/sdk`.

### A sidebar shell

A complete `layout.app`: the brand, the links between their slots, the panel's search, the user, the
scheme switch, sign-out, and the page in `<Main>`. `w-sidebar` reads `--layout-sidebar-width` and
`bg-sidebar` reads `--sidebar`.

<!-- sample: level3-sidebar/src/views/Shell.tsx -->
```tsx
import { LayoutGrid, LogOut, Moon, Shield, Sun, UserRound, type LucideIcon } from 'lucide-react';
import { Avatar, PanelLink, SpinnerOverlay } from '@pterodactyl/sdk';
import { Main, Slot, View, type AppLayoutLink, type ViewProps } from '@pterodactyl/sdk/theme';

const icons: Record<AppLayoutLink['id'], LucideIcon> = { dashboard: LayoutGrid, admin: Shield, account: UserRound };

const item = [
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted-foreground no-underline',
    'hover:bg-popover hover:text-foreground data-[status=active]:bg-primary/15 data-[status=active]:text-foreground',
].join(' ');

/** A sidebar on large screens and a top bar below them, with the page beside it. */
export default function Shell({ model, children }: ViewProps<'layout.app'>) {
    const { brand, user, scheme, setScheme } = model;

    return (
        <div className='flex min-h-dvh flex-col lg:flex-row'>
            <SpinnerOverlay visible={model.signingOut} fixed />
            <aside className='flex shrink-0 items-center gap-2 bg-sidebar p-3 lg:sticky lg:top-0 lg:h-dvh lg:w-sidebar lg:flex-col lg:items-stretch lg:p-4'>
                <PanelLink destination={{ to: '/' }} className='flex h-10 items-center px-3 font-semibold no-underline'>
                    {brand.logoUrl ? <img src={brand.logoUrl} alt={brand.name} className='h-8 w-auto' /> : brand.name}
                </PanelLink>
                <nav aria-label='Main' className='flex flex-1 gap-1 lg:flex-col'>
                    <Slot name='nav.items.before' />
                    {model.links.map((link) => {
                        const Glyph = icons[link.id];

                        return (
                            <PanelLink key={link.id} destination={link.destination} exact={link.exact} className={item}>
                                <Glyph className='size-4' aria-hidden />
                                <span className='max-lg:sr-only'>{link.label}</span>
                            </PanelLink>
                        );
                    })}
                    <Slot name='nav.items.after' />
                </nav>
                <View name='app.search' className={item} />
                <div className='flex items-center gap-2'>
                    <Avatar name={user.uuid} size={28} />
                    <span className='min-w-0 flex-1 truncate text-sm max-lg:sr-only'>{user.username}</span>
                    {/* setScheme is null unless the theme offers both schemes. */}
                    {setScheme && (
                        <button
                            type='button'
                            aria-label={scheme === 'dark' ? 'Use the light scheme' : 'Use the dark scheme'}
                            onClick={() => setScheme(scheme === 'dark' ? 'light' : 'dark')}
                            className={item}
                        >
                            {scheme === 'dark' ? (
                                <Sun className='size-4' aria-hidden />
                            ) : (
                                <Moon className='size-4' aria-hidden />
                            )}
                        </button>
                    )}
                    <button type='button' aria-label='Sign out' onClick={model.signOut} className={item}>
                        <LogOut className='size-4' aria-hidden />
                    </button>
                </div>
            </aside>
            <Main className='min-w-0 flex-1'>{children}</Main>
        </div>
    );
}
```
<!-- /sample -->

## Level 4: rebuild pages

A **page** view renders one route, such as `page.server.console`. A **block** view is a piece of a page,
such as `server.power` (the power buttons) or `server.terminal`. A page is a composition of blocks, so
rearranging a page is a short layer that places the same blocks differently.
[Finding the right view](#finding-the-right-view) leads from a URL to the view that draws it.

### Models and inputs

Models are data plus actions. `model.actions.start()` of `server.power` sends the signal through the
panel's own request, permission check and notifications; a view only decides what to show and when to
call it. The [view catalogue](#view-catalogue) names each model type, and the types document each field.

Some views also take **inputs**: props passed by whatever renders the view, which its layers receive
beside `model`, such as the `server` of `dashboard.serverCard`.

A few models hold an engine component: `model.Terminal` of `server.terminal` draws the console on a
canvas, `model.Editor` of `server.files.editor` is the code editor, and the auth forms carry `Captcha`.
Give them a sized box and colour them with the `--terminal-*` and `--editor-*` tokens.

### `Default`

`<Default />` with no props renders the layer below as it would have rendered, so wrapping it adds a
frame. It also takes `model`, `className`, `children` and `parts`: `className` is merged with the
layer's own, and the others replace what the layer below receives.

<!-- sample: snippets/views.tsx#transform -->
```tsx
/** Hands the layer below another model and an extra class. */
function LoudCard({ model, Default }: ViewProps<'dashboard.serverCard'>) {
    return <Default model={{ ...model, name: model.name.toUpperCase() }} className='gap-6' />;
}
```
<!-- /sample -->

Four views publish **parts** that `Default` swaps one at a time: `dashboard.serverCard`,
`server.files.details`, `server.files.editor` and `server.files.manager`. A part receives the model and
inputs, typed by `ViewPartProps<'name'>`. Define parts outside the layer's render function.

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

### Placing views

`<View name="…" />` renders a view: it runs the view's hook where it is placed, resolves its layers and
renders the top one. Each view has a **context**: `app` (every signed-in page), `server`, `account`,
`admin` or `auth`. A view renders anywhere its context exists and throws outside it, such as a server
view on the dashboard. Pass inputs as props: `<View name="dashboard.serverCard" server={server} />`.

`<View name="billing.balance" optional />` renders nothing when no installed package defines the view,
for blocks that extensions define. A layer for such a view is typed loosely: its `model` is `unknown`.

### Slots in your views

A layer that replaces a view renders that view's slots, listed in the [catalogue](#view-catalogue), so
extensions mounted there keep their place. The models carry what the slots receive:
`<Slot name="server.console.before" data={model.server} />`. `<Default />` renders the slots of the
layers below. In development, a layer that renders none of its view's slots logs a warning after three
seconds.

The **engine slots** of a page, listed in their own column of the catalogue, are rendered by the engine
before and after the page view, so a layer neither renders nor drops them.

### Loading with `lazyView`

`lazyView(() => import('./views/ConsolePage'))` loads a view's chunk the first time it renders, and the
router preloads it with its route; `lazyView(importer, 'Name')` takes a named export. While the chunk
loads, the view shows its placeholder. If it fails, the layer below renders.

The panel loads the theme entry before its first render, so keep it small. Make every view lazy except
`layout.auth`, which renders before anything else, and import only `@pterodactyl/sdk/theme` in the
modules the entry loads eagerly. Views that use components from `@pterodactyl/sdk` belong behind
`lazyView`.

### A console with power controls in the header

`layout.server` keeps classic's layout through `Default` and adds a header that places the
`server.power` block. `model.conflict` is set while the server installs, transfers or is suspended;
classic then shows a notice instead of the page.

<!-- sample: level4-console/src/views/ServerLayout.tsx -->
```tsx
import { View, type ViewProps } from '@pterodactyl/sdk/theme';

/** Classic's server layout, under a header with the server's name and its power controls. */
export default function ServerLayout({ model, Default }: ViewProps<'layout.server'>) {
    return (
        <>
            <header className='mx-auto flex w-full max-w-panel flex-wrap items-center gap-4 px-4 pt-6'>
                <h1 className='min-w-0 flex-1 truncate text-xl font-semibold'>{model.name}</h1>
                {/* No power controls while the server installs, transfers or is suspended. */}
                {model.conflict === null && <View name='server.power' />}
            </header>
            <Default />
        </>
    );
}
```
<!-- /sample -->

`page.server.console` composes the console without `server.power`. This page has no engine slots, so it
renders `server.console.before` and `server.console.after` itself. It stays mounted while another server
page shows, and `active` says whether it is the page on show. The theme registers both with `lazyView`,
as in [level 3](#level-3-change-the-layout).

<!-- sample: level4-console/src/views/ConsolePage.tsx -->
```tsx
import { Alert, ServerContentBlock } from '@pterodactyl/sdk';
import { Slot, View, type ServerConsoleModel, type ViewProps } from '@pterodactyl/sdk/theme';

const notices: Record<NonNullable<ServerConsoleModel['notice']>, string> = {
    maintenance: 'This node is under maintenance, so the server cannot be controlled.',
    installing: 'The server is installing; most actions wait until it finishes.',
    transferring: 'The server is moving to another node.',
};

/**
 * The console without `server.power`, which the layout's header shows. The page stays mounted while
 * another server page shows, so it only titles the document while `active`.
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

### Opaque views

The admin pages and `app.search` still render the panel's own containers. Their model is `OpaqueModel`
(empty) and they have no fixtures. Wrap them with `Default`, or place blocks around them.

## Settings, schemes and branding

### Settings

`theme.settings` in the manifest declares up to 64 values that administrators change on
**Admin > Themes**. Each setting has a `type`, a `label`, an optional `help` and `default`, and writes
its value to a token (`token`), an attribute on `<html>` (`attribute`), both, or neither.

<!-- sample: settings/extension.json -->
```json
{
    "$schema": "./node_modules/@pterodactyl/sdk/manifest.schema.json",
    "id": "sunrise",
    "type": "theme",
    "name": "Sunrise",
    "version": "1.0.0",
    "requires": { "panel": "^2.0.0-dev", "sdk": ">=2.0.0-beta.5 <3.0" },
    "ui": { "entry": "dist/client.js" },
    "theme": {
        "stylesheet": "dist/theme.css",
        "schemes": ["dark", "light"],
        "settings": {
            "accent": { "type": "color", "label": "Accent", "default": "#f97316", "token": "--sunrise-accent" },
            "density": {
                "type": "select",
                "label": "Density",
                "options": { "comfortable": "Comfortable", "compact": "Compact" },
                "default": "comfortable",
                "attribute": "data-sunrise-density"
            },
            "logo": { "type": "image", "label": "Logo", "token": "--brand-logo" },
            "tagline": { "type": "text", "label": "Tagline", "default": "Every server, one place.", "maxLength": 80 }
        }
    }
}
```
<!-- /sample -->

The server writes tokens into `<style id="theme-settings">` as `:root:root { … }`, which outranks the
stylesheet's `:root`, `.dark` and `.light` rules, and attributes onto `<html>`, before any CSS loads, so
nothing flashes. Use a token for a value and an attribute for a choice:

<!-- sample: settings/src/theme.css#settings -->
```css
/* The "accent" setting writes --sunrise-accent; the colour roles derive from it. */
:root {
    --primary: var(--sunrise-accent, #f97316);
    --ring: color-mix(in oklab, var(--primary) 60%, transparent);
}

/* The "density" setting writes data-sunrise-density on <html>. */
[data-sunrise-density='compact'] {
    --spacing: 0.2rem;
}
```
<!-- /sample -->

| Type | Options | Value | Token | Attribute |
| --- | --- | --- | --- | --- |
| `color` | | `#rgb`, `#rgba`, `#rrggbb`, `#rrggbbaa` or `oklch(L C H [/ A])` | The colour, lower case | The colour |
| `select` | `options`: value to label; values are CSS identifiers | One of the option values | The value, as an identifier | The value |
| `toggle` | | `true` or `false` | `1` or `0` | `true` or `false` |
| `number` | `min`, `max`, `step`, `unit` (`%` or up to 4 letters) | A number within the limits, on a step | The number and unit: `0.75rem` | The number |
| `text` | `maxLength`, 255 unless set (1 to 1000) | A string | A quoted, escaped CSS string | The text |
| `image` | | An upload: PNG, JPEG, GIF, WebP, AVIF or plain SVG, up to 1 MB. No default. | `url("…")` | The URL |

A token is a custom property name; an attribute is a `data-*` name other than `data-theme`, which the
panel writes itself. Two settings cannot write the same one, setting names match
`^[a-zA-Z][a-zA-Z0-9_]{0,47}$`, and a default passes the same checks as a saved value. A stored value
that no longer fits, such as an option you removed, reads as the default.

### Settings in code

`useThemeSettings()` returns the values of the whole theme chain as the page loaded them.
`npm run types` (`p:extension:types`) writes `src/theme-types.ts`, whose `ThemeSettings` types them. This
is how a setting with no token or attribute, such as a tagline, is read:

<!-- sample: settings/src/AuthLayout.tsx#read -->
```tsx
const { tagline } = useThemeSettings<ThemeSettings>();
```
<!-- /sample -->

### Schemes

`useTheme()` returns `{ id, chain, scheme, schemes, setScheme }`. `setScheme('dark' | 'light' | 'system')`
stores the choice in the `pterodactyl_scheme` cookie and swaps the class on `<html>` without a reload.
`system` follows the device, and a choice the theme does not offer falls back to its first scheme. The
`layout.app` model carries the same `scheme`, `schemes` and `setScheme`, which is null unless the theme
offers both; the [sidebar shell](#a-sidebar-shell) uses it for its switch.

### Branding

- **Logo.** An `image` setting that writes `--brand-logo` becomes `model.brand.logoUrl` of `layout.app`
  and `layout.auth`. Classic shows it in place of the panel's name.
- **Icons.** `defineTheme({ icons: { server: Boxes } })` maps lucide icon names to components.
  `<NamedIcon>`, which draws navigation and extension icons by name, uses the theme's icon first.
- **Browser colour.** The `--theme-color` token sets `<meta name="theme-color">`.
- **Side navigation.** Classic's account and server navigation moves into a sidebar on wide screens
  while `<html>` has `data-sub-navigation="side"`. A `select` setting with that attribute and the
  options `side` and `top` lets administrators choose.

### Building on another theme

`theme.extends` names an installed theme to build on; it is `classic` when left out. The chain runs
classic, the parent and its parents, then the child:

- **Views and skins** stack in that order, so the child's `Default` renders the parent's layer.
- **Settings** are the union of the chain's. A child's setting of the same name replaces the parent's,
  which is how a child changes a default.
- **Schemes** are the child's when it declares them, else the nearest parent's.
- **Stylesheet.** The panel links the nearest stylesheet in the chain. A child with its own replaces
  the parent's whole, so import the parent's source CSS into yours to build on it.
- The parent has to be installed and enabled first, and cannot be disabled while the child is. A
  missing parent or a cycle stops the child from activating. Pin its version with `requires.extensions`.

[`docs-samples/child-theme`](docs-samples/child-theme) in the panel repository extends Aurora, the full
example.

## Tools

### Finding the right view

1. **Name it from the URL.** A route's page is usually `page.` plus the area and path: `/server/<id>/backups`
   is `page.server.backups`, `/account/api` is `page.account.api`, and `/` is `page.dashboard`. Blocks
   are named after their area and what they draw, such as `server.power`, and the layouts after their
   area.
2. **Look it up.** The [view catalogue](#view-catalogue), or `npx pterodactyl-theme list`, gives its
   model type, inputs, parts, slots and fixtures.
3. **Read classic's source.** `npx pterodactyl-theme eject page.server.console` copies it into
   `src/views`. A page's source shows the blocks it places with `<View name="…" />` and the slots it
   renders, which leads you to the block you are after.
4. **Render it.** `renderView` draws the view from its fixtures in a test, with your layers over
   classic (see [Testing](#testing)).

A layer that throws is logged in the browser console as `[extensions] "<theme>" failed in view "<name>"`,
with the error, and the layer below renders in its place.

### The theme CLI

The SDK installs a `pterodactyl-theme` binary:

```sh
npx pterodactyl-theme list [--kind layout|page|block] [--json]
npx pterodactyl-theme eject <view> [--out src/views] [--entry src] [--force]
npx pterodactyl-theme skins [primitive]
```

- `list` prints every view with its kind, context and fixtures, marking those classic has source for.
- `eject` copies classic's source for a view, with the classic files it imports, into `src/views`,
  rewrites its imports to `@pterodactyl/sdk`, and prints the import and `views` entry to add. The copy
  compiles as it is. It overwrites the view's file only with `--force`, and keeps shared files an
  earlier eject copied. Opaque views have no source; wrap them with `Default`.
- `skins` lists the primitives, or prints classic's skin of one with the constants it reads.

### Testing

Every view ships **fixtures**, named stand-in models listed in the catalogue, so a test can render it
without a panel. The scaffold's `vitest.config.mjs` uses `@pterodactyl/sdk/vitest`, which points the SDK
at its real runtime, and `@pterodactyl/sdk/testing` renders with your theme:

- `renderView(name, { theme, fixture, model, input })` renders a view synchronously with the theme's
  layers over classic, from the named fixture, the first fixture, or `model`. Nested views read their
  first fixture. It returns `{ container, unmount }`; each call replaces the previous render.
- `renderPrimitive(name, props, { theme })` renders a primitive under the theme's skins.
- `preloadTheme(theme)` loads the theme's `lazyView` chunks. Await it in `beforeAll`: `renderView`
  throws until it has.
- `createViewTestHost(name, { model })` renders one layer by hand with typed `props`, for asserting on
  actions with `vi.fn()`.

<!-- sample: level4-console/src/theme.spec.tsx#tests -->
```tsx
let rendered: RenderedView | undefined;

beforeAll(async () => {
    // jsdom has no ResizeObserver; classic's server navigation, which `Default` renders, measures itself.
    vi.stubGlobal(
        'ResizeObserver',
        class {
            observe() {}
            disconnect() {}
        }
    );
    // The views are lazy; loading their chunks first lets renderView render them synchronously.
    await preloadTheme(theme);
});
afterEach(() => rendered?.unmount());

test('puts the power controls in the server header', () => {
    rendered = renderView('layout.server', { theme, fixture: 'owner' });

    const header = screen.getByRole('heading', { name: 'Survival' }).closest('header');

    expect(header?.textContent).toContain('Restart');
});
```
<!-- /sample -->

### Doctor and pack

`npm run doctor` runs `p:extension:doctor` on the theme: the manifest, the build, the
[budgets](#budgets), remote URLs, view names, and whether the stylesheet was built against this panel's
classes. `npm run pack` writes a `.pteroext` archive to install on another panel.

## Rules and guarantees

### What a theme must not do

- **Load from another origin.** Doctor, pack, install and enable refuse CSS that loads anything from
  another origin: `@import url(https://…)`, a font on a CDN, any `http:` or `//` URL. Data URLs are
  allowed.
- **Drop slots.** A layer that replaces a view renders its slots, or the extensions mounted there
  disappear.
- **Replace interactive primitives.** Only presentational primitives take a component. Any other is a
  type error, and is ignored with a warning at runtime.
- **Import CSS from JavaScript.** The build fails: the panel loads only `dist/theme.css`.
- **Bypass the engine on forms.** A layer over a form spreads each field's `fieldProps` (name, type,
  autocomplete, value and handlers) onto an input. A layer over `auth.loginForm` also submits through
  `model.submit`, renders `Captcha` when the panel requires one, and keeps the `auth.login.form.after`
  slot.

Beyond `<Main>`, a view owns its markup's accessibility: label controls, name navigation landmarks
(`<nav aria-label="…">`), and keep visible focus styles.

### Fallback and safe mode

- **Boot.** The panel loads the chain's entries in parallel before its first render, for at most 2.5
  seconds. When an entry fails, does not export a `defineTheme` result, or is too slow, that browser
  shows classic, logs the error, and shows root admins a notice.
- **Layers.** A layer that throws is logged against its package in the browser console, and the layer
  below renders in its place.
- **Safe mode.** Opening any page with `?safe_mode=1` renders that session with classic and no extension
  bundles; `?safe_mode=0` leaves it. The Themes page links to both.
- **Stylesheet fallback.** `dist/theme.json` records the hash of the panel classes the stylesheet was
  built with. When the panel's differ because the panel was updated after the build, the panel links its
  own stylesheet and the theme's over it, so classes added since still exist. Doctor and pack warn;
  rebuilding with the panel's SDK makes the theme's stylesheet the only one again.
- **Compatibility.** An active theme whose `requires` no longer match after an update is marked failed
  on the Extensions page, and every page renders classic until it is updated.

`p:theme:reset` returns to classic and `p:theme:list` lists the themes. The active theme cannot be
disabled or removed.

### Versions

Declare `requires.panel` and `requires.sdk` (`>=2.0.0-beta.5 <3.0`). During the 2.0 betas, view names,
models, parts and fixtures, and the primitives' parts and state, can change in any beta. Build against
the SDK of the panel you target. After updating it, `npm run typecheck` catches a changed model field
and `npm run doctor` an overridden view that no longer exists; then rebuild. From 2.0, additions to the
contract are minor versions and removals major.

### Budgets

| File | Limit |
| --- | --- |
| `dist/theme.css` | 250 KB |
| `dist/client.js` | 200 KB; chunks loaded with `lazyView` do not count |

Doctor, pack, install and enable refuse a build over either limit, or a `dist/theme.json` that names a
core view that does not exist.

## Reference

### Tokens

Every custom property of the panel stylesheet, with what classic uses it for. The utilities column names
the Tailwind classes that read it, such as `bg-card` and `text-card` for `*-card`. The declarations, with
classic's values for both schemes, are in `@pterodactyl/sdk/panel.css` and
`@pterodactyl/sdk/theme-tokens.css`. The terminal and the editor re-read their tokens whenever a
stylesheet or an attribute of `<html>` changes.

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
| `--layout-gutter` | Side margin of page content until it centres; `page-width` applies both. |  |
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

### Primitives

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

The components are exported from `@pterodactyl/sdk` with their props types: `Button` (with `Button.Text`
and `Button.Danger`), `LinkButton`, `RouterLinkButton`, `NewButton`, `NewLinkButton`, `Input`,
`TextArea`, `NumberInput`, `FileInput`, `Select`, `Checkbox`, `Switch`, `Label`, `Card`, `ListRow`,
`PageHeading`, `Badge`, `Alert`, `ProgressBar`, `Dialog` (with `Dialog.Footer`, `Dialog.Icon`,
`Dialog.Confirm`, `Dialog.ConfirmTrigger`), `Tooltip`, `DropdownMenu`, `DropdownMenuItem`,
`ContextDropdownMenu`, `Table`, `Pagination`, `Empty` and its parts, `Spinner`, `SpinnerOverlay`,
`Avatar`, `Code` and `CopyOnClick`. Replacing `Button` leaves `LinkButton` and `RouterLinkButton`, which
render links with the `Button` skin, as they are.

### View catalogue

Every view with its context, model type, inputs and parts, slots and fixtures. **Slots** are rendered by
the view's layers; a page's **engine slots** are rendered around it by the engine.
`npx pterodactyl-theme list` prints the same from `@pterodactyl/sdk/views.json`.

<!-- generated:views -->
#### Layouts

| View | Context | Model | Inputs and parts | Slots | Fixtures |
| --- | --- | --- | --- | --- | --- |
| `layout.account` | account | `AccountLayoutModel` |  | `account.navigation.before` `account.navigation.after` | default, extension |
| `layout.admin` | admin | `AdminLayoutModel` |  | `panel.navigation.before` `panel.navigation.after` | default, extension |
| `layout.app` | app | `AppLayoutModel` |  | `nav.items.before` `nav.items.after` | user, admin, schemes, logo, accountArea, serverArea, adminArea |
| `layout.auth` | auth | `AuthLayoutModel` |  |  | default, logo |
| `layout.server` | server | `ServerLayoutModel` |  | `server.navigation.before` `server.navigation.after` | owner, subuser, admin, offline, installing, suspended |

#### Pages

| View | Context | Model | Inputs and parts | Slots | Engine slots | Fixtures |
| --- | --- | --- | --- | --- | --- | --- |
| `page.account.activity` | account | `ActivityPageModel` |  |  | `account.activity.before` `account.activity.after` | default |
| `page.account.api` | account | `AccountListPageModel` |  |  | `account.api.before` `account.api.after` | default, error |
| `page.account.overview` | account | `AccountOverviewModel` |  | `account.overview.before` `account.overview.after` |  | default, twoFactorRequired |
| `page.account.ssh` | account | `AccountListPageModel` |  |  | `account.ssh.before` `account.ssh.after` | default, error |
| `page.auth.checkpoint` | auth | `AuthPageModel` |  |  | `auth.checkpoint.before` `auth.checkpoint.after` | default |
| `page.auth.login` | auth | `AuthPageModel` |  |  | `auth.login.before` `auth.login.after` | default |
| `page.auth.password` | auth | `AuthPageModel` |  |  | `auth.password.before` `auth.password.after` | default |
| `page.auth.passwordReset` | auth | `AuthPageModel` |  |  | `auth.passwordReset.before` `auth.passwordReset.after` | default |
| `page.dashboard` | app | `DashboardModel` |  |  |  | default, error |
| `page.server.activity` | server | `ActivityPageModel` |  |  | `server.activity.before` `server.activity.after` | default |
| `page.server.backups` | server | `BackupsPageModel` |  |  | `server.backups.before` `server.backups.after` | default, loading, error |
| `page.server.console` | server | `ServerConsoleModel` | `active: boolean` | `server.console.before` `server.console.after` |  | default, installing, maintenance |
| `page.server.databases` | server | `ServerPageModel` |  |  | `server.databases.before` `server.databases.after` | default, error |
| `page.server.files` | server | `ServerPageModel` |  | `server.files.before` `server.files.after` |  | default, error |
| `page.server.files.editor` | server | `FileEditorPageModel` |  |  | `server.files.editor.before` `server.files.editor.after` | file, new, loading, error |
| `page.server.network` | server | `ServerPageModel` |  |  | `server.network.before` `server.network.after` | default, error |
| `page.server.schedules` | server | `SchedulesPageModel` |  |  | `server.schedules.before` `server.schedules.after` | default, error |
| `page.server.schedules.detail` | server | `ScheduleDetailPageModel` |  |  | `server.schedules.detail.before` `server.schedules.detail.after` | default, loading, error |
| `page.server.settings` | server | `ServerSettingsPageModel` |  |  | `server.settings.before` `server.settings.after` | default |
| `page.server.startup` | server | `StartupPageModel` |  | `server.startup.form` | `server.startup.before` `server.startup.after` | default, loading, error |
| `page.server.users` | server | `SubusersPageModel` |  |  | `server.users.before` `server.users.after` | default, loading, error |
| `page.server.users.create` | server | `SubuserCreatePageModel` |  |  | `server.users.create.before` `server.users.create.after` | default, loading |

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
| `page.admin.users` | `panel.users.before` `panel.users.after` |
| `page.admin.users.create` | `panel.users.create.before` `panel.users.create.after` |
| `page.admin.users.detail` | `panel.users.detail.before` `panel.users.detail.after` |
| `page.admin.users.detail.about` |  |
<!-- /generated:views -->

### The full example

Aurora, in [`docs-samples/aurora`](docs-samples/aurora) of the panel repository, puts the levels together:
a glass sidebar shell, a split sign-in page, a card-grid dashboard with power buttons, the console with
power controls in the header, skins, settings and tests. The panel's checks compile it against this
package's types, run its tests and build it.

## Glossary

- **Block**: a view that is a piece of a page, such as `server.power`. It renders anywhere its context
  exists.
- **Fixture**: a named stand-in model of a view, so a test can render the view without a panel.
- **Input**: a prop a view takes besides its model, passed by whatever renders it, such as the
  `server` of `dashboard.serverCard`.
- **Layer**: one package's component for a view, or its skin for a primitive. Classic is the bottom
  layer.
- **Layout**: a view that frames an area's pages and places them in `<Main>`, such as `layout.app`.
- **Model**: the data and actions the engine produces for a view, and the view's only data source.
- **Page**: a view that renders one route, such as `page.server.console`, composed of blocks.
- **Part**: a named element of a primitive (`root`, `title`), or of one of the views that publish parts.
- **Primitive**: a component of the UI kit, such as `Button` or `Dialog`, styled by skins.
- **Skin**: the recipes, by part, of one primitive.
- **Slot**: a named place where extensions mount content.
- **Token**: a CSS custom property the panel's classes read, such as `--primary`.
- **View**: a named layout, page or block: an engine model and the stack of layers that render it.
