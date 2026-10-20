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
throws gives way to the layer below ([Fallback](#fallback-and-safe-mode)). Whatever you do not override
stays classic.

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
php artisan p:extension:make sunrise --theme --css-only
cd extensions/sunrise && npm install
php ../../artisan p:extension:dev . --enable
php ../../artisan p:theme:activate sunrise
php ../../artisan p:extension:dev . --watch
```

`--css-only` leaves out the JavaScript that levels 2 to 4 need. The package goes in
`extensions/sunrise`, or with `--out=<dir>` in `<dir>/sunrise`.

While the last command runs on a panel with `APP_DEBUG=true`, a stylesheet change swaps into open pages
without a reload, and any other change reloads them. To try a theme without switching everyone to it,
skip `p:theme:activate` and choose **Preview for me** on **Admin > Themes**.

At this level you edit only `src/theme.css`. The panel links its own stylesheet, then the theme's, so your
rules win. A stylesheet of plain CSS, such as tokens and `[data-ui]` selectors, needs nothing else. The
scaffold's starts with `@import '@pterodactyl/sdk/theme-entry.css';`, which a theme that uses Tailwind
classes or `@theme` keeps: it builds the panel's stylesheet with the theme's classes added, so its
utilities are ordered with the panel's. Write plain Tailwind with no prefix.

### What the build writes

`npm run build` writes these files to `dist/`:

| File | What it is |
| --- | --- |
| `theme.css` | The stylesheet the panel links after its own. |
| `theme.json` | The parent themes the stylesheet was built over. Doctor and the panel check it. |
| `theme-source.css` and `classes.txt` | The theme's own CSS and the classes its files use, which the build of a [child theme](#building-on-another-theme) brings in. |
| `client.js` | From level 2 on, the theme's JavaScript. |

### Tokens and colour roles

A **token** is a CSS custom property the panel's classes read. Set one and everything that reads it
changes: `--primary` colours primary buttons and selected items, `--radius` sets every rounded corner,
and `--spacing` scales every padding, margin and gap. [Tokens](#tokens) lists them all with their roles.

The colour classes are roles such as `bg-card` and `text-muted-foreground`. Of Tailwind's palette only
`white` and `black` remain (`text-white`, `bg-black/15`). Add a colour of your own in `@theme`:
`--color-brand: oklch(0.7 0.2 300);` gives `bg-brand`.

### Dark and light

`<html>` has the class `dark` or `light`. Put each scheme's colours in `.dark { }` and `.light { }`,
and values for both in `:root`. The manifest's `theme.schemes` lists the schemes the theme offers, the
default first. Classic offers dark only and its light palette is provisional, so a theme that offers
light sets its own light colours.

### Fonts

Ship font files with the theme and reference them relative to the stylesheet; the build copies them
beside `dist/theme.css`. Set `--font-sans` (body) and `--font-header` (headings) in `@theme`.

Fonts and images from another origin work too: `@import url('https://fonts.googleapis.com/css2?…');`
after the entry import (the build moves it to the top of `dist/theme.css`, where CSS requires it), or a
`url(https://…)` in `@font-face`. The panel does not block them. Every visitor's browser then requests
that host, and the theme looks broken while it is down, so bundling is the safer default.

### Shadows

The build compiles the whole panel stylesheet with your `@theme`, so Tailwind's own theme variables are
tokens too. Classic lifts cards, stat tiles, chart cards and dialogs with the shadow scale (`shadow-sm`
to `shadow-lg`); set `--shadow-sm` and the rest to change every one, as the sample below does to draw
hairlines instead.

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

    /* Classic lifts cards, tiles and dialogs with Tailwind's shadow scale; these draw a hairline instead. */
    --shadow-sm: 0 0 0 1px var(--border);
    --shadow-md: 0 0 0 1px var(--border);
    --shadow-lg: 0 0 0 1px var(--border);
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

The manifest has no `ui` and `vite.config.mjs` no `entry`, so the build writes no `client.js`;
`--css-only` scaffolds a theme this way. [`docs-samples/level1-css`](docs-samples/level1-css) in the panel
repository holds the files.

You now have a theme.

## Level 2: restyle components

From this level on, a theme has JavaScript: `src/theme.tsx`, built to the `dist/client.js` that the
manifest's `ui.entry` names. Scaffold without `--css-only` to get it, with a skin, a layout and a test
to start from; a level 1 theme moves over by copying its `src/theme.css` into the new package.

The panel draws its interface with a UI kit of 31 **primitives**, such as `Button`, `Card` and `Dialog`.
Extensions use them too, so restyling a primitive restyles it everywhere.

Each primitive has named **parts** (a `Card` has `root`, `header`, `title`, `icon`, `actions` and
`body`) and **state** (a `Button` has `color`, `size`, `secondary` and `loading`). A **skin** gives
parts a **recipe**: the classes the part gets in a given state. `skin(name, { part: recipe })` types a
skin by its primitive, and `src/theme.tsx` maps primitives to skins with `defineTheme({ ui: skins })`:

<!-- sample: level2-skins/src/skins.tsx#skins -->
```tsx
export const skins = {
    Tooltip: tooltip,
    Badge: badge,
    Button: button,
    Card: card,
    Stat: stat,
    Alert: Banner,
} satisfies ThemeSkins;
```
<!-- /sample -->

`npx pterodactyl-theme skins Button` copies classic's file that skins `Button`, `skins/buttons.ts`, into
`src/skins` to start from.

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

A recipe **replaces** the recipe below it for its part, whole: classes are never merged, so keep the
ones a part needs to work, such as `z-9999` on a tooltip. Parts a skin leaves out keep the recipes below.

A function recipe also receives `below`, the classes the layers below give the part in that state.
Return `below(extra)` to **extend** them instead: a class of `extra` replaces the one below it conflicts
with, so `rounded-lg` replaces `rounded-sm` rather than sitting beside it. One skin can do both, part by
part:

<!-- sample: level2-skins/src/skins.tsx#extend -->
```tsx
const card = skin('Card', {
    // Replaces the title recipe below.
    title: 'text-base font-semibold text-foreground',
    // Builds on the root recipe below: its classes, with `rounded-lg` in place of their `rounded-sm`.
    root: (state, below) => below('rounded-lg ring-1 ring-border/60'),
});

/** Stat values in tabular figures, and labels in the body face, on top of classic's stats. */
const stat = skin('Stat', {
    value: (state, below) => below('tabular-nums'),
    label: (state, below) => below('font-sans uppercase tracking-wide'),
});
```
<!-- /sample -->

`cn` from `@pterodactyl/sdk/theme` merges classes the same way anywhere else. A `className` passed where a
primitive is used is merged last, so it wins: `<Button className="mt-4">` replaces the recipe's own `mt-*`
class.

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
| `Stat` | A labelled value, such as a server's memory: console tiles and dashboard metrics | `root`, `icon`, `glyph`, `label`, `value`, `limit` and 2 more | yes |

[Primitives](#primitives) lists all 31 with their state. A recipe that throws renders no classes.

## Level 3: change the layout

A **view** is a named piece of the interface, rendered by a stack of layers. A **layout** view frames a
set of pages:

| Layout | Frames | Its model has |
| --- | --- | --- |
| `layout.app` | Every signed-in page | `brand`, `links`, `area`, `user`, the scheme, `signOut` |
| `layout.account` | The account pages, inside `layout.app` | `nav` |
| `layout.server` | A server's pages, inside `layout.app` | `name`, `state`, `nav`, `conflict`, `server` |
| `layout.admin` | The admin area, inside `layout.app` | `nav`, `user` |
| `layout.auth` | The sign-in pages | `brand` |

A view's component receives its **model**, the data and actions the engine produces for it, with
`Default`, `className` and `children`. A layout's `children` is the routed page. `ViewProps<'name'>`
types these props. Register views by name:

<!-- sample: level3-sidebar/src/theme.tsx -->
```tsx
import type { ReactNode } from 'react';
import { Main, defineTheme, lazyView } from '@pterodactyl/sdk/theme';

/** The shell draws every area's navigation, so an area's layout only places its pages. */
function AreaLayout({ children }: { children?: ReactNode }) {
    return <Main>{children}</Main>;
}

export default defineTheme({
    views: {
        'layout.app': lazyView(() => import('./views/Shell')),
        'layout.server': AreaLayout,
        'layout.account': AreaLayout,
        'layout.admin': AreaLayout,
    },
});
```
<!-- /sample -->

`lazyView` loads a view's code the first time it renders (see [Level 4](#loading-with-lazyview)).

### `<Main>`

A layout places `children` inside `<Main>`, which renders `<main id="main" tabIndex={-1}>`: the target of
the skip link, which the engine focuses after each navigation while it announces the new page title.
Layouts nest, and only the outermost `<Main>` renders the element; a nested one renders its children in
place, in a `<div>` when it has a `className`.

### Navigation and the brand

`layout.app` has `model.links` (the dashboard, the admin area for root admins, and the account) and
`model.brand`, the panel's `name` and a `logoUrl` (see [Branding](#branding)).

The other layouts each frame an **area**, a part of the panel with navigation of its own: the account, a
server, or the admin area. They have `model.nav.items`: the area's `NavItem`s, filtered by subuser
permissions and egg rules, extension screens included. Links and items have an `icon`, a lucide name for
`<NamedIcon>`, and a `destination` for `PanelLink` and `usePanelNavigate()`. `<Navigation items>` calls
its child with each item and `{ active, badge }`, and keeps an extension screen hidden until its runtime
condition allows it. `NavigationLink` links to an item and carries `data-status="active"` while the item
is active. [`AreaNav`](#the-shell-knows-the-area) below uses both.

`useNavigation('account' | 'server' | 'admin')` returns an area's items outside its layout. Classic's tab
bar, which folds items that do not fit into a menu, is `SubNavigation` with `SubNavigationLayout` from
`@pterodactyl/sdk`. In a menu of your own, `<DropdownMenu.Item destination={{ to: '/account' }}>` is a
link that navigates and closes the menu, and `<DropdownMenu.Item checked={…}>` a choice such as the
scheme in use, announced as checked and skinned through the item's `checked` state.

### Keyboard shortcuts

`useShortcut(keys, handler, { when })` from `@pterodactyl/sdk` calls `handler` when `keys` is pressed: a
key as `KeyboardEvent.key` names it, such as `d` or `/`, after `mod+` for Cmd on Apple devices and Ctrl
elsewhere. Keys typed in a field, a dialog or a menu are theirs, a key another handler took first is
skipped, and `when: false` turns it off. It returns the shortcut's `aria` name for `aria-keyshortcuts`
and a `label` to show, such as `⌘/` or `Ctrl+/`:

<!-- sample: snippets/views.tsx#shortcut -->
```tsx
/** A link of `layout.app` that a key opens, such as D for the dashboard. */
function ShortcutLink({ link, keys }: { link: AppLayoutLink; keys: string }) {
    const navigate = usePanelNavigate();
    const shortcut = useShortcut(keys, () => void navigate(link.destination));

    return (
        <PanelLink destination={link.destination} aria-keyshortcuts={shortcut.aria}>
            {link.label} <kbd>{shortcut.label}</kbd>
        </PanelLink>
    );
}
```
<!-- /sample -->

The panel's own shortcut is `mod+/`, which opens search; `model.shortcut` of `app.search` names it.

### The shell knows the area

`model.area` of `layout.app` says which area the user is in: `kind` is `account`, `server` or `admin`,
with the model of that area's layout beside it, or `dashboard` on the dashboard and any page outside an
area. `AccountArea`, `ServerArea`, `AdminArea` and `DashboardArea` type each one. So `layout.app` can
draw every area's navigation itself, in one sidebar. The theme then overrides `layout.account`,
`layout.server` and `layout.admin` with a **thin layout**, which places the page in `<Main>` and nothing
else (`AreaLayout` above).

A layer that replaces a view renders the view's slots, listed in the [catalogue](#view-catalogue), or
the extensions mounted there disappear. The area layouts' slots sit in their navigation, so the shell
renders them where it draws that navigation (`AreaSlot` below); the catalogue lists them with
`layout.app`.

<!-- sample: level3-sidebar/src/views/AreaNav.tsx -->
```tsx
import { Badge, NamedIcon } from '@pterodactyl/sdk';
import {
    Navigation,
    NavigationLink,
    Slot,
    type AccountArea,
    type AdminArea,
    type AppArea,
    type ServerArea,
} from '@pterodactyl/sdk/theme';

type Area = AccountArea | ServerArea | AdminArea;

const link = [
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted-foreground no-underline',
    'hover:bg-popover hover:text-foreground data-[status=active]:bg-primary/15 data-[status=active]:text-foreground',
].join(' ');

/** The slots of the area's navigation, which its own layout would render if it drew the navigation. */
function AreaSlot({ area, at }: { area: Area; at: 'before' | 'after' }) {
    if (area.kind === 'server') return <Slot name={`server.navigation.${at}`} data={area.server} />;
    if (area.kind === 'account') return <Slot name={`account.navigation.${at}`} />;
    return <Slot name={`panel.navigation.${at}`} />;
}

/** The pages of the area the user is in: a server's, the account's or the admin area's. */
export default function AreaNav({ area }: { area: AppArea }) {
    if (area.kind === 'dashboard') return null;

    const items = area.kind === 'server' && area.adminLink ? [...area.nav.items, area.adminLink] : area.nav.items;
    const label = { server: 'Server', account: 'Account', admin: 'Administration' }[area.kind];

    return (
        <nav aria-label={label} className='flex flex-col gap-1'>
            {area.kind === 'server' && (
                <p className='flex items-center gap-2 px-3 pb-1 text-sm font-semibold'>
                    <span className='min-w-0 flex-1 truncate'>{area.name}</span>
                    <Badge size='sm' tone={area.state === 'running' ? 'success' : 'neutral'}>
                        {area.state ?? 'unknown'}
                    </Badge>
                </p>
            )}
            <AreaSlot area={area} at='before' />
            <Navigation items={items}>
                {(item, { badge }) => (
                    <NavigationLink item={item} className={link}>
                        {item.icon && <NamedIcon name={item.icon} size={16} aria-hidden />}
                        <span className='min-w-0 flex-1 truncate'>{item.label}</span>
                        {badge}
                    </NavigationLink>
                )}
            </Navigation>
            <AreaSlot area={area} at='after' />
        </nav>
    );
}
```
<!-- /sample -->

The fixtures `accountArea`, `serverArea` and `adminArea` of `layout.app` render the shell in each area.

### Slots in narrow regions

What an extension puts in a slot is its own markup, at its own width. Contain it where you place it:
`min-w-0` and `overflow-hidden` on the element around the slot, `truncate` for a line of text, and grid
tracks of `minmax(0, 1fr)` (`grid-cols-1`) so one wide item cannot widen a column.

A rail of icons is too narrow for most slot content. Render its slots in a wider panel beside it, below
its items, or in a top bar: a shell that is only a rail gives `nav.items.before` and `.after` a top bar.
Where a slot does sit somewhere narrow, say so with `compact`: `<Slot name='nav.items.after' compact />`
passes `compact` to the components mounted there, which render a smaller version.
When such a shell reorders its regions on phones, make the rail, the bar, the area's navigation and the
page children of one grid, so each breakpoint places them with `grid-template-areas`.

### A sidebar shell

A complete `layout.app`: the brand, the links between their slots, the area's navigation, the panel's
search, the user, the scheme switch, sign-out, and the page in `<Main>`. `app.search` draws the search
trigger and its dialog; its model (`open`, `field`, `results` with their `destination`) lets a shell
draw search in its own bar instead. `w-sidebar` reads `--layout-sidebar-width` and `bg-sidebar` reads
`--sidebar`. Below the `lg` breakpoint the sidebar is a drawer, which an effect on
`usePanelLocation().pathname` closes when one of its links navigates.

<!-- sample: level3-sidebar/src/views/Shell.tsx -->
```tsx
import { useEffect, useState } from 'react';
import { LogOut, Menu, Moon, Sun } from 'lucide-react';
import { Avatar, NamedIcon, PanelLink, SpinnerOverlay, cn, usePanelLocation } from '@pterodactyl/sdk';
import { Main, Slot, View, type ViewProps } from '@pterodactyl/sdk/theme';
import AreaNav from './AreaNav';

const item = [
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted-foreground no-underline',
    'hover:bg-popover hover:text-foreground data-[status=active]:bg-primary/15 data-[status=active]:text-foreground',
].join(' ');

/** A sidebar beside the page on large screens, and a drawer behind a menu button below them. */
export default function Shell({ model, children }: ViewProps<'layout.app'>) {
    const { brand, user, scheme, setScheme } = model;
    const SchemeIcon = scheme === 'dark' ? Sun : Moon;
    const [open, setOpen] = useState(false);
    const { pathname } = usePanelLocation();

    // Close the drawer when one of its links navigates.
    useEffect(() => setOpen(false), [pathname]);

    return (
        <div className='min-h-dvh lg:flex'>
            <SpinnerOverlay visible={model.signingOut} fixed />
            <button
                type='button'
                aria-expanded={open}
                aria-controls='sidebar'
                onClick={() => setOpen(!open)}
                className={cn(item, 'm-2 lg:hidden')}
            >
                <Menu className='size-4' aria-hidden />
                Menu
            </button>
            <aside
                id='sidebar'
                className={cn(
                    'flex-col gap-4 bg-sidebar p-4 lg:sticky lg:top-0 lg:flex lg:h-dvh lg:w-sidebar lg:shrink-0 lg:overflow-y-auto',
                    open ? 'flex' : 'hidden'
                )}
            >
                <PanelLink destination={{ to: '/' }} className='flex h-10 items-center px-3 font-semibold no-underline'>
                    {brand.logoUrl ? <img src={brand.logoUrl} alt={brand.name} className='h-8 w-auto' /> : brand.name}
                </PanelLink>
                <nav aria-label='Main' className='flex flex-col gap-1'>
                    <Slot name='nav.items.before' />
                    {model.links.map((link) => (
                        <PanelLink key={link.id} destination={link.destination} exact={link.exact} className={item}>
                            <NamedIcon name={link.icon} size={16} aria-hidden />
                            {link.label}
                        </PanelLink>
                    ))}
                    <Slot name='nav.items.after' />
                </nav>
                <AreaNav area={model.area} />
                <View name='app.search' className={item} />
                <div className='mt-auto flex items-center gap-2'>
                    <Avatar name={user.uuid} size={28} />
                    <span className='min-w-0 flex-1 truncate text-sm'>{user.username}</span>
                    {/* setScheme is null unless the theme offers both schemes. */}
                    {setScheme && (
                        <button
                            type='button'
                            aria-label={scheme === 'dark' ? 'Use the light scheme' : 'Use the dark scheme'}
                            onClick={() => setScheme(scheme === 'dark' ? 'light' : 'dark')}
                            className={item}
                        >
                            <SchemeIcon className='size-4' aria-hidden />
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

### Lining up with the page

Page content is at most `--layout-content-width` wide and centred, never closer than `--layout-gutter`
to the sides of the region it is in, with `--layout-page-spacing` above and below. The `page-width`
class applies the same geometry, so a header a layout draws above its pages lines up with them:
`<header className="page-width mt-4 sm:mt-6">`. `usePageTitle()` returns the title the page on show
gave the document, such as `Survival | Console`, for a shell to show in its own bar.

## Level 4: rebuild pages

A **page** view renders one route, such as `page.server.console`. A **block** view is a piece of a page,
such as `server.power` (the power buttons) or `server.terminal`. A page is a composition of blocks, so
rearranging a page is a short layer that places the same blocks differently.
[Finding the right view](#finding-the-right-view) leads from a URL to the view that draws it.

### Models and inputs

Models are data plus actions. `model.actions.start()` of `server.power` sends the signal through the
panel's own request, permission check and notifications; a view only decides what to show and when to
call it. The [view catalogue](#view-catalogue) names each model type, and the types document each field.

Server views share one vocabulary. `ServerRunState` (`offline`, `starting`, `running`, `stopping`) is
what a server is doing in `layout.server`, `server.power`, `server.stats` and the dashboard cards.
`ServerStatMetric` is a usage value with its `limit` (0 for unlimited) and an `alarm` of `warning` or
`danger`, a `UiTone` to pass on as it is: `<Stat tone={metric.alarm ?? 'neutral'} />`. `ServerConflict`
is why a server's pages are unavailable.

Each server of `dashboard.serverList` is the model of its `dashboard.serverCard`, with the `server` the
row slots receive: its state and usage, its `permissions` (`start`, `stop`, `restart`, `console`) and
`actions` that send power signals through the API. `totals.servers` counts the servers on every page;
`totals.runningOnPage` and `totals.offlineOnPage` count only the page loaded.

Some views also take **inputs**: props passed by whatever renders the view, which its layers receive
beside `model`, such as the `server` of `dashboard.serverCard`.

A few models hold an engine component: `model.Terminal` of `server.terminal` draws the console on a
canvas, `model.Editor` of `server.files.editor` is the code editor, and `page.auth.login` carries `Captcha`.
Give them a sized box and colour them with the [`--terminal-*` and `--editor-*` tokens](#console).

### `Default`

`<Default />` with no props renders the layer below as it would have rendered, so wrapping it adds a
frame. It also takes `model`, `className` and `children`: `className` is merged with the layer's own,
and the others replace what the layer below receives.

<!-- sample: snippets/views.tsx#transform -->
```tsx
/** Hands the layer below another model and an extra class. */
function LoudCard({ model, Default }: ViewProps<'dashboard.serverCard'>) {
    return <Default model={{ ...model, name: model.name.toUpperCase() }} className='gap-6' />;
}
```
<!-- /sample -->

Three views place smaller views for their pieces: `dashboard.serverCard` places
`dashboard.serverCard.identity`, `.address` and `.metrics`; `server.files.editor` places `.notice`,
`.canvas`, `.language` and `.actions`; and `server.files.manager` places `.toolbar`, `.list` and
`.selection`. Each piece takes the model of its view as its `model` input, so a theme overrides one piece
and keeps the others. A layer that draws the whole view places the pieces it keeps itself:
`<View name="dashboard.serverCard.metrics" model={model} server={server} />`.

<!-- sample: snippets/views.tsx#nested -->
```tsx
/** Replaces the metrics of every server card; the card keeps its identity and address. */
function Uptime({ model, className }: ViewProps<'dashboard.serverCard.metrics'>) {
    return <span className={className}>{model.state.kind === 'ready' ? model.state.power : 'unknown'}</span>;
}
```
<!-- /sample -->

### Placing views

`<View name="…" />` renders a view: it runs the view's hook where it is placed, resolves its layers and
renders the top one. Each view has a **context**, where its hook has the data it reads: `app` (every
signed-in page), `server`, `account`, `admin` or `auth`. A server view needs a server page around it,
for example. Pass inputs as props: `<View name="dashboard.serverCard" server={server} />`.

`className` lands on the outermost element a view renders, in every classic view, so
`<View name="server.sftp" className="h-full" />` stretches the card itself. Keep the rule in your own
layers: a page's blocks are placed by the classes they are given. A layer with no single outer element,
such as a server card drawn as the cells of a table row, gives `className` to each cell or ignores it.

### Slots in your views

A layer that replaces a view renders its slots ([level 3](#the-shell-knows-the-area)); `<Default />`
renders the slots of the layers below. The models carry what the slots receive:
`<Slot name="server.console.before" data={model.server} />`.

The dashboard's row slots belong to a row: `dashboard.serverRow.before` and `.after` around it,
`.metrics.after` after its usage, and `.name.after` (rendered by `dashboard.serverCard`) after the name.
A row drawn as table cells keeps them in its cells: `before` at the start of the first, `name.after` in
the name's, and `metrics.after` and `after` in the last, each contained as
[in narrow regions](#slots-in-narrow-regions).

The **engine slots** of a page, listed in their own column of the catalogue, are rendered by the engine
before and after the page view, so a layer neither renders nor drops them.

### Loading with `lazyView`

`lazyView(() => import('./views/ConsolePage'))` loads a view's chunk the first time it renders, and the
router preloads it with its route; `lazyView(importer, 'Name')` takes a named export. While the chunk
loads, the view shows its placeholder. If it fails, the layer below renders.

The panel loads the theme entry before its first render, so keep it small. Make every view lazy except
`layout.auth`, which renders before anything else, and layers as small as a thin layout. The entry and
the modules it imports eagerly, skins included, may import `react`, `lucide-react` and
`@pterodactyl/sdk/theme` (which has `cn`); anything from `@pterodactyl/sdk`, such as `Button` or
`AreaChart`, belongs in a module behind `lazyView`.

### Charts

`AreaChart` from `@pterodactyl/sdk` draws what `server.graphs` samples: `data` is its points (`t`, a
`performance.now()` time, and a value per series), `series` names each value's `dataKey` and `color`,
and `live` and `windowMs` keep the plot sliding over the window the model gives. `suggestedMax` sets a
y-axis maximum that grows with the data, `tickFormatter` formats the axis, and `height` defaults to
180 pixels; the width follows its box. Classic frames each chart in a `ChartCard`.

### A full-bleed page

`PageContentBlock` and `ServerContentBlock` title a page and frame its content with the
[page geometry](#lining-up-with-the-page). Anything outside them spans the region the layout gives the
page.

- **Part of a page edge to edge**, such as a console above sections of the usual width: render that part
  beside the block, not in it. A page view can return `<><Screen /><ServerContentBlock>…</ServerContentBlock></>`.
- **A whole page edge to edge**: set the geometry tokens on its block, where they cascade to everything in
  it: `className='[--layout-content-width:100%] [--layout-gutter:0px] [--layout-page-spacing:0px]'`.

The copyright line below every page is the `PageFooter` primitive: skin it, or replace it with a
component that renders nothing to drop it and draw your own in the shell.

### A console with power controls in the header

`layout.server` keeps classic's layout through `Default` and adds a header, lined up with the page by
[`page-width`](#lining-up-with-the-page), that places the `server.power` block. `model.conflict` is set
while the server installs, transfers or is suspended; classic then shows a notice instead of the page.
`model.state` is what the server is doing (`offline`, `starting`, `running` or `stopping`, or null until
Wings reports it), kept live by the server's websocket, for a status badge.

<!-- sample: level4-console/src/views/ServerLayout.tsx -->
```tsx
import { View, type ViewProps } from '@pterodactyl/sdk/theme';

/** Classic's server layout, under a header with the server's name and its power controls. */
export default function ServerLayout({ model, Default }: ViewProps<'layout.server'>) {
    return (
        <>
            <header className='page-width mt-4 flex flex-wrap items-center gap-4 sm:mt-6'>
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
renders `server.console.before` and `server.console.after` itself. It places the prompt views that
`model.features` names, each of which opens itself when the server's output asks for it. It stays mounted
while another server page shows, and `active` says whether it is the page on show. The theme registers
both with `lazyView`, as in [level 3](#level-3-change-the-layout).

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
            <View name='server.graphs' className='mt-4 gap-4' />
            <Slot name='server.console.after' data={model.server} />
            {model.features.map((name) => (
                <View key={name} name={name} />
            ))}
        </ServerContentBlock>
    );
}
```
<!-- /sample -->

The admin pages are not views. `layout.admin` wraps them, and each renders the `panel.*` slots before and
after it, so a theme restyles them through the layout and the [skins](#level-2-restyle-components), and
extensions place blocks in those slots.

### Slots, screens and setup

A theme's entry can do what an extension's does in `setup`: put components in slots, register the
screens its manifest declares under `ui.screens`, and add table columns and form fields. Pass `setup` to
`defineTheme`. It receives the same context as an extension's and runs while the theme, or a theme
extending it, is active, so nothing it registers shows under another theme. Views stay in `views`.

```tsx
export default defineTheme({
    views: { 'layout.app': AppLayout },
    setup({ slots, screens }) {
        slots.register('server.console.before', UptimeBanner);
        screens.register('status', () => import('./StatusPage'));
    },
});
```

## Settings, schemes and branding

### Settings

`theme.settings` in the manifest declares values that administrators change on
**Admin > Themes**. Each setting has a `type`, a `label`, an optional `help` and `default`, and writes
its value to a token (`token`), an attribute on `<html>` (`attribute`), both, or neither. A `tab` puts
it in a section of the settings dialog, picked from a list; settings without one go under General.

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
| `text` | `maxLength`, 255 unless set (at least 1) | A string | A quoted, escaped CSS string | The text |
| `image` | | An upload: PNG, JPEG, GIF, WebP, AVIF or plain SVG, up to 10 MB. No default. | `url("…")` | The URL |

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

`useTheme()` returns `{ id, chain, scheme, preference, schemes, setScheme }`. `scheme` is the scheme in
use and `preference` what the user chose, `system` included, for a switch or a `checked` menu item to
show as selected.
`setScheme('dark' | 'light' | 'system')` stores the choice in the `pterodactyl_scheme` cookie and swaps
the class on `<html>` without a reload. `system` follows the device as it changes, and a choice the
theme does not offer falls back to its first scheme. The `layout.app` model carries the same `scheme`,
`preference`, `schemes` and `setScheme`, which is null unless the theme offers both; the
[sidebar shell](#a-sidebar-shell) uses it for its switch.

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
classic, the parent and its parents, then the child. `p:extension:make sunset --theme --extends=aurora`
scaffolds a child: a manifest naming the parent, a stylesheet of one import, an empty `defineTheme` and a
test of the chain.

- **Views and skins** stack in that order, so the child's `Default` renders the parent's layer, and a
  recipe's `below` is the parent's classes.
- **Settings** are the union of the chain's. A child's setting of the same name replaces the parent's;
  declare it in full, with the parent's token to change what the parent's setting writes.
- **Schemes** are the child's when it declares them, else the nearest parent's.
- The parent has to be installed and enabled first, and cannot be disabled while the child is. A
  missing parent or a cycle stops the child from activating. Pin its version with `requires.extensions`.

<!-- sample: child-theme/extension.json -->
```json
{
    "$schema": "./node_modules/@pterodactyl/sdk/manifest.schema.json",
    "id": "aurora-sunset",
    "type": "theme",
    "name": "Aurora Sunset",
    "version": "1.0.0",
    "requires": { "panel": "^2.0.0-dev", "sdk": ">=2.0.0-beta.5 <3.0", "extensions": { "aurora": "^1.0" } },
    "ui": { "entry": "dist/client.js" },
    "theme": {
        "extends": "aurora",
        "stylesheet": "dist/theme.css",
        "settings": {
            "accent": { "type": "color", "label": "Accent", "default": "#f97316", "token": "--aurora-accent" },
            "glow": { "type": "toggle", "label": "Sunset glow", "default": true, "attribute": "data-sunset-glow" }
        }
    }
}
```
<!-- /sample -->

The panel links its own stylesheet, then the nearest theme stylesheet in the chain, so a child's stylesheet
carries its parents'.
A child's build reads `theme.extends`, finds each parent's build, and brings in its `theme-source.css` and
`classes.txt` ([what the build writes](#what-the-build-writes)), oldest first, right after the SDK's
import. The child's `src/theme.css` stays one import and its own rules, and can use the parents'
utilities, variants and fonts:

<!-- sample: child-theme/src/theme.css -->
```css
/* The panel stylesheet. The build brings in Aurora's after it, so this file only retunes Aurora. */
@import '@pterodactyl/sdk/theme-entry.css';

/* Aurora's tokens, warmer. Its glass, sidebar and skins read them. */
:root {
    --radius: 1rem;
    --glass-border: color-mix(in oklab, var(--primary) 20%, transparent);
}

.dark {
    --background: oklch(0.18 0.03 40);
    --card: oklch(0.24 0.035 40);
}

/* The "glow" setting writes data-sunset-glow on <html>; `glass` is Aurora's utility. */
[data-sunset-glow='true'] .glass {
    box-shadow: 0 0 2rem -1rem var(--primary);
}
```
<!-- /sample -->

The build looks for each parent first in `<extensions>/<id>`, where `extensions` is the panel's extensions
directory passed to `defineThemeConfig` (the scaffold writes it), then in `node_modules/<id>`, for a parent
installed as a dependency. It lists the parents it built over in `dist/theme.json`, and doctor, pack and
enable refuse a child built without them. Rebuild the child when a parent changes. A parent's `@plugin`
is loaded from the child's packages, so install the same plugins.

The child's tests render the chain with `themes`, importing each parent's built entry, which
`pterodactylTestAliases({ extensions })` finds where the build does and the SDK types:

<!-- sample: child-theme/src/theme.spec.tsx#tests -->
```tsx
// The chain the panel stacks over classic: Aurora, then this theme.
const themes = [aurora, theme];
let rendered: RenderedView | undefined;

beforeAll(() => preloadTheme(themes));
afterEach(() => rendered?.unmount());

test("frames Aurora's server card", () => {
    rendered = renderView('dashboard.serverCard', { themes, fixture: 'running' });

    // `Default` is Aurora's card, not classic's.
    expect(rendered.container.querySelector('.border-l-2 > .space-y-3 .font-mono')).not.toBeNull();
});

test("adds a shadow to Aurora's pill buttons", () => {
    rendered = renderWithTheme(<Button>Save</Button>, { themes });

    expect(rendered.container.querySelector('button')?.className).toContain('rounded-full');
    expect(rendered.container.querySelector('button')?.className).toContain('shadow-primary/20');
});
```
<!-- /sample -->

`import aurora from 'aurora/dist/client.js'` brings in the parent. `npm run types` types `useThemeSettings()`
with the settings of the whole chain. [`docs-samples/child-theme`](docs-samples/child-theme) in the panel
repository extends the Aurora sample.

## Tools

### Finding the right view

1. **Name it from the URL.** A route's page is usually `page.` plus the area and path: `/server/<id>/backups`
   is `page.server.backups`, `/account/api` is `page.account.api`, and `/` is `page.dashboard`. Blocks
   are named after their area and what they draw, such as `server.power`, and the layouts after their
   area.
2. **Look it up.** The [view catalogue](#view-catalogue) gives its model type, inputs, slots and
   fixtures, and `npx pterodactyl-theme list` its kind, context and fixtures.
3. **Read classic's source.** `npx pterodactyl-theme eject page.server.console` copies it into
   `src/views`. A page's source shows the blocks it places with `<View name="…" />` and the slots it
   renders, which leads you to the block you are after.
4. **Render it.** `renderView` draws the view from its fixtures in a test, with your layers over
   classic (see [Testing](#testing)).

A layer that fails names its view in the browser console ([Fallback](#fallback-and-safe-mode)).

### The theme CLI

The SDK installs a `pterodactyl-theme` binary:

```sh
npx pterodactyl-theme list [--kind layout|page|block] [--json]
npx pterodactyl-theme eject <view> [--out src/views] [--entry src] [--force]
npx pterodactyl-theme skins [primitive] [--out src/skins] [--entry src] [--force]
```

- `list` prints every view with its kind, context and fixtures, marking those classic has source for.
- `eject` copies classic's source for a view, with the classic files it imports, into `src/views`,
  rewrites its imports to `@pterodactyl/sdk`, and prints the import and `views` entry to add. The copy
  compiles as it is. It overwrites the view's file only with `--force`, and keeps shared files an
  earlier eject copied.
- `skins` lists each primitive with classic's file that skins it. Given a primitive, it copies that file
  into `src/skins` the way `eject` copies a view, and prints the import and `ui` entry to add.

### Testing

Every view ships **fixtures**, named stand-in models listed in the catalogue, so a test can render it
without a panel. The scaffold's `vitest.config.mjs` uses `@pterodactyl/sdk/vitest`, which points the SDK
at its real runtime and adds what jsdom lacks: an inert `ResizeObserver` and `IntersectionObserver`, and
a `matchMedia` that matches nothing. Tests need no stubs of their own; `vi.stubGlobal` still replaces
one that a test drives. `@pterodactyl/sdk/testing` renders with your theme:

- `renderView(name, { themes, fixture, model, input, className, slots, prefix })` renders a view
  synchronously with the layers of `themes` over classic, from the named fixture, the first fixture, or
  `model`. A theme passes `themes: [theme]`; a child theme passes its chain, oldest first, as
  `themes: [parent, theme]` ([Building on another theme](#building-on-another-theme)). A nested view
  placed with inputs reads the model they give, so each row of a list shows its own server, and any
  other nested view reads its first fixture; `usePanelLocation()` returns the path with empty `params`
  and `search`. It returns `{ container, unmount }`; each call replaces the previous render. Pass
  `vi.fn()` callbacks in `model` to assert on actions.
- `slots` mounts components in slots, typed per slot, as an extension's `slots.register` would. Use it
  to test that a layer renders the slots of the view it replaces.
- `renderWithTheme(element, { themes })` renders any element under the themes' skins, such as
  `<Button>Save</Button>`.
- `preloadTheme(themes)` loads the `lazyView` chunks of the themes. Await it in `beforeAll`:
  `renderView` throws until it has.

To test the stylesheet, build it and read `dist/theme.css` with `readFileSync('dist/theme.css', 'utf8')`
from `node:fs`: Vitest runs in the theme's directory, and the scaffold's `tsconfig.json` has Node's
types. When Vitest runs under an AI agent it hides what passing tests log; `npx vitest run
--reporter=default` shows it.

`renderView` is strict: a theme or extension layer that throws, in the view or a nested one, fails the
render with a `ViewLayerError` naming the `view`, the `layer` and the `cause`, such as
`The "theme" layer of view "server.graphs" failed: …`. A failure after the first render, such
as on a click, is thrown by `unmount()`. It also fails when the theme leaves out a slot that classic
renders for the same fixture, while the view that owns the slot is on the page: a view the theme moves
elsewhere takes its slots along, and a theme's `layout.app` renders the area layouts' slots for them.
`{ strict: false }` lets layers fall back and slots go missing as in the panel.

<!-- sample: level4-console/src/theme.spec.tsx#tests -->
```tsx
const themes = [theme];
let rendered: RenderedView | undefined;

// The views are lazy; loading their chunks first lets renderView render them synchronously.
beforeAll(() => preloadTheme(themes));
afterEach(() => rendered?.unmount());

test('puts the power controls in the server header', () => {
    rendered = renderView('layout.server', { themes, fixture: 'owner' });

    const header = screen.getByRole('heading', { name: 'Survival' }).closest('header');

    expect(header?.textContent).toContain('Restart');
});
```
<!-- /sample -->

The [sidebar shell](#a-sidebar-shell)'s tests mount a component in a slot of the server's navigation,
then check that the shell renders it:

<!-- sample: level3-sidebar/src/theme.spec.tsx#slots -->
```tsx
rendered = renderView('layout.app', {
    themes,
    fixture: 'serverArea',
    slots: { 'server.navigation.after': ({ data }) => <span>Mods for {data.attributes.name}</span> },
});
```
<!-- /sample -->

### Doctor and pack

`npm run doctor` runs `p:extension:doctor` on the theme: the manifest, the build, and for a child
theme, whether the stylesheet was built over its parents.

`npm run pack` writes a `.pteroext` archive to install on another panel, replacing an earlier one;
`.gitignore` leaves archives out of the repository. The build writes no source maps, which would embed
the theme's sources, unless `vite.config.mjs` passes `defineThemeConfig({ sourcemap: true })`, and pack
leaves `*.map` files out unless `p:extension:pack` is given `--sourcemaps`.

## Rules and guarantees

### What a theme must not do

- **Drop slots.** A layer that replaces a view renders its slots, or the extensions mounted there
  disappear ([level 3](#the-shell-knows-the-area)). `renderView` fails on a dropped slot, and the
  browser console names the layer, the slot and the extensions it hides.
- **Replace interactive primitives.** Only presentational primitives take a component. Any other is a
  type error, and the panel keeps its own.
- **Import CSS from JavaScript.** The build fails: the panel loads only `dist/theme.css`.
- **Bypass the engine on forms.** A layer over a form spreads each field's `fieldProps` (name, type,
  autocomplete, value and handlers) onto an input. A layer over `page.auth.login` also submits through
  `model.submit`, renders `Captcha` when the panel requires one, and keeps the `auth.login.form.after`
  slot.

Beyond `<Main>`, a view owns its markup's accessibility: label controls, name navigation landmarks
(`<nav aria-label="…">`), and keep visible focus styles.

### The page around your views

What a theme can rely on when it draws behind or around the panel, such as a background layer:

- `<html>` has the scheme class, `data-theme` and the settings' attributes. `<body>` has the page
  background (`bg-background`) and `position: relative`, and holds `#app` (after `#modal-portal` once
  signed in).
- Your layouts render inside `#app`, which is `isolation: isolate`: a stacking context. A layer in it
  with a negative `z-index`, such as `fixed inset-0 -z-10 pointer-events-none`, paints behind the
  panel's content and in front of the body's background.
- Dialogs, menus, select popups, tooltips and the copy notice render outside `#app`, in `<body>`, so
  they paint above everything inside it whatever its `z-index`. Toasts are fixed on top of `#app`.
- `#app` and the engine's wrappers set no `transform`, `filter` or `contain`, so `position: fixed` in a
  layout is fixed to the window. Nothing sets `overflow` on `html`, `body` or `#app`: the window scrolls,
  and `position: sticky` works in layouts. Keep it that way; a background image belongs on `body` in the
  stylesheet, or on a fixed layer in `layout.app`.

### Fallback and safe mode

- **Boot.** The panel loads the chain's entries in parallel before its first render, for at most 15
  seconds, as long as an extension bundle may take. When an entry fails to load or is too slow, that
  browser shows classic, logs the error, and shows root admins a notice. A view of an entry that is not
  a component falls back like a layer that throws, and an icon that is not one is skipped.
- **Layers.** A layer that throws falls back to the layer below it, once. If that one throws too,
  classic included, the view shows the panel's error notice, whose Retry tries the failed layer again.
  Each failure logs one line in the browser console:
  `[extensions] "<layer id>" failed in view "<view>": <error>`.
- **Safe mode.** Opening any page with `?safe_mode=1` renders that session with classic and no extension
  bundles; `?safe_mode=0` leaves it. The Themes page links to both.
- **Compatibility.** An active theme whose `requires` no longer match after an update is marked failed
  on the Extensions page, and every page renders classic until it is updated.

`p:theme:reset` returns to classic and `p:theme:list` lists the themes. The active theme cannot be
disabled or removed.

### Versions

Declare `requires.panel` and `requires.sdk` (`>=2.0.0-beta.5 <3.0`). During the 2.0 betas, view names,
models and fixtures, and the primitives' parts and state, can change in any beta. Build against
the SDK of the panel you target. After updating it, `npm run typecheck` catches a changed model field
or an overridden view the new SDK does not have; then rebuild. From 2.0, additions to the
contract are minor versions and removals major.

## Reference

### Tokens

Every custom property of the panel stylesheet, with what classic uses it for. The utilities column names
the Tailwind classes that read it, such as `bg-card` and `text-card` for `*-card`. The declarations, with
classic's values for both schemes, are in `@pterodactyl/sdk/panel.css` and
`@pterodactyl/sdk/theme-tokens.css`. The terminal and the editor re-read their tokens whenever a
stylesheet or an attribute of `<html>` changes. Any colour syntax works, `oklch()` and `color-mix()`
included: the engine converts the terminal's colours to the hex and `rgba()` its canvas reads.

#### Surfaces and text

| Token | Role | Utilities |
| --- | --- | --- |
| `--background` | Page background. | `*-background` |
| `--foreground` | Body text. | `*-foreground` |
| `--card` | Cards, rows and raised panels. | `*-card` |
| `--card-foreground` | Text on cards. | `*-card-foreground` |
| `--popover` | Raised fills: menus, tooltips, hover fills, stat tiles and chart cards. | `*-popover` |
| `--popover-foreground` | Text on popovers. | `*-popover-foreground` |
| `--muted` | Quiet fills: table heads, disabled inputs. | `*-muted` |
| `--muted-foreground` | Secondary text, icons and placeholders. | `*-muted-foreground` |
| `--sunken` | A recessed surface on a card: meter tracks, code chips. | `*-sunken` |
| `--border` | Borders and dividers. | `*-border` |
| `--input` | Input fills. | `*-input` |
| `--input-border` | Input borders; defaults to `--input`. | `*-input-border` |
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
| `--chart-*` | Five chart series colours (`--chart-1` to `--chart-5`); generated avatars draw from them too. |  |

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
| `--layout-gutter` | The least space beside page content, which centres when wider; `page-width` applies both. |  |
| `--layout-page-spacing` | Space above and below a page's content (`1rem`, `1.5rem` from `sm`). |  |
| `--layout-auth-width` | Width of the sign-in card (`max-w-auth`). | `*-auth` |
| `--layout-sidebar-width` | Width of sidebars (`w-sidebar`). | `*-sidebar` |
| `--spinner-overlay-opacity` | Opacity of the background behind a loading overlay. |  |

#### Shadows

| Token | Role | Utilities |
| --- | --- | --- |
| `--shadow-*` | Tailwind's shadow scale (`shadow-2xs` to `shadow-2xl`) that lifts cards, tiles and dialogs; retune it in `@theme`. |  |
| `--shadow-navigation-active` | The underline of the active navigation item (`shadow-navigation-active`). |  |
| `--shadow-progress` | The glow under the page progress bar (`shadow-progress`). |  |

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

### Primitives

<!-- generated:primitives -->
| Primitive | Parts | State on `root` | Replaceable | Notes |
| --- | --- | --- | --- | --- |
| Alert | `root`, `icon`, `body`, `title`, `content` | `data-type`: success, info, warning, danger | yes |  |
| Avatar | `root` | `data-variant`: beam, marble, pixel, sunset, ring, bauhaus | yes |  |
| Badge | `root` | `data-tone`: neutral, success, warning, danger, info, accent; `data-size`: sm, md; `data-variant`: solid, soft | yes |  |
| Button | `root`, `spinner`, `label` | `data-color`: green, red, orange, primary, grey; `data-size`: xsmall, small, large, xlarge; `data-secondary`; `data-loading` | yes | `label` wraps the children in a row with a gap, so an icon beside the text needs no margin. `Button.Text` is `color: grey` and `Button.Danger` is `color: red`. |
| Card | `root`, `header`, `title`, `icon`, `actions`, `body` | `data-titled`; `data-actions`; `data-loading` | yes |  |
| ChartCard | `root`, `header`, `title`, `legend`, `body` | none | yes | The frame of a chart: `legend` sits at the end of the header, `body` holds the chart. |
| Checkbox | `root`, `indicator` | none | no |  |
| Code | `root` | `data-dark` | no |  |
| CopyOnClick | `root`, `status`, `message`, `target` | `data-status`: copied, failed | no | `target` is added to the clicked child; the other parts are the status toast. |
| Dialog | `backdrop`, `viewport`, `container`, `root`, `body`, `content`, `header`, `heading`, `title`, `description`, `spacer`, `footer`, `close`, `closeButton`, `closeIcon`, `icon`, `iconGlyph` | `data-described`; `data-type`: danger, info, success, warning | no | `type` is the state of the `icon` part. |
| DropdownMenu | `trigger`, `positioner`, `root` | `data-context` | no |  |
| DropdownMenuItem | `root`, `icon`, `glyph`, `text`, `label`, `description` | `data-danger`; `data-described`; `data-checked` | no | `checked` is set on an item given `checked`, such as the scheme in use. |
| Empty | `root`, `header`, `media`, `title`, `description`, `content` | `data-size`: default, compact; `data-variant`: default, icon | yes | `variant` is the state of the `media` part. |
| FileInput | `root` | none | no |  |
| Input | `root` | `data-light`; `data-error` | no |  |
| Label | `root` | `data-light` | no |  |
| ListRow | `root`, `icon`, `title`, `description`, `status` | `data-interactive`; `data-status`: neutral, success, warning, danger, info, accent | yes | `icon`, `title` and `description` are `ListRow.Icon`, `ListRow.Title` and `ListRow.Description`, placed by the row's content; `status` is drawn when the row has a `status`. |
| NewButton | `root`, `content`, `icon` | `data-link` | no | `root` is added to the Button it renders, `content` wraps the icon and label. |
| NumberInput | `root`, `input` | `data-light`; `data-error` | no |  |
| PageFooter | `root`, `text`, `link` | none | yes | The copyright line below each page; a replacement that renders nothing drops it. |
| PageHeading | `root`, `text`, `title`, `description`, `actions` | none | yes |  |
| Pagination | `root`, `page` | `data-current` | no | `page` is added to each page Button. |
| ProgressBar | `root`, `indicator` | `data-variant`: meter, page; `data-tone`: neutral, success, warning, danger, info, accent | yes |  |
| Select | `root`, `input`, `trigger`, `positioner`, `popup`, `list`, `item`, `itemLabel`, `indicator`, `empty`, `group`, `groupLabel`, `summary`, `preview` | `data-error`; `data-multiple`; `data-selected`; `data-rich-label`; `data-searching` | no |  |
| Spinner | `root`, `container` | `data-size`: small, base, large; `data-blue`; `data-centered` | yes | `root` is the spinning element, an empty `span` with no text or role: the region that loads announces it. `container` centres it when `centered`. A skin can animate anything, such as the `content` of `::before`. |
| SpinnerOverlay | `root`, `text` | `data-fixed` | no |  |
| Stat | `root`, `indicator`, `icon`, `glyph`, `body`, `label`, `value`, `limit` | `data-variant`: tile, inline; `data-tone`: neutral, success, warning, danger, info, accent; `data-unavailable` | yes | `glyph` is the icon inside `icon`; `indicator` is a bar for the tone, and `limit` follows `value`. `unavailable` means the value says why there is no reading, such as "Offline". |
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

Every view with its context, model type, inputs, slots and fixtures. **Slots** are rendered by
the view's layers; a page's **engine slots** are rendered around it by the engine. A page whose model is
`PageStatusModel` loads data its blocks need: it shows a placeholder while `loading`, and `error` with a
`retry` in place of its blocks. A failed refresh keeps the loaded data on show, so `error` stays null.
`npx pterodactyl-theme list` prints each view's kind, context and fixtures from `@pterodactyl/sdk/views.json`.

<!-- generated:views -->
#### Layouts

| View | Context | Model | Inputs | Slots | Fixtures |
| --- | --- | --- | --- | --- | --- |
| `layout.account` | account | `AccountLayoutModel` |  | `account.navigation.before` `account.navigation.after` | default, extension |
| `layout.admin` | admin | `AdminLayoutModel` |  | `panel.navigation.before` `panel.navigation.after` | default, extension |
| `layout.app` | app | `AppLayoutModel` |  | `nav.items.before` `nav.items.after`<br>where it draws an area's navigation: `account.navigation.before` `account.navigation.after` `server.navigation.before` `server.navigation.after` `panel.navigation.before` `panel.navigation.after` | user, admin, schemes, logo, accountArea, serverArea, adminArea |
| `layout.auth` | auth | `AuthLayoutModel` |  |  | default, logo |
| `layout.server` | server | `ServerLayoutModel` |  | `server.navigation.before` `server.navigation.after` | owner, subuser, admin, offline, installing, suspended |

#### Pages

| View | Context | Model | Inputs | Slots | Engine slots | Fixtures |
| --- | --- | --- | --- | --- | --- | --- |
| `page.account.activity` | account | `ActivityPageModel` |  |  | `account.activity.before` `account.activity.after` | default |
| `page.account.api` | account | `PageStatusModel` |  |  | `account.api.before` `account.api.after` | default, loading, error |
| `page.account.overview` | account | `AccountOverviewModel` |  | `account.overview.before` `account.overview.after` |  | default, twoFactorRequired, avatars |
| `page.account.ssh` | account | `PageStatusModel` |  |  | `account.ssh.before` `account.ssh.after` | default, loading, error |
| `page.auth.checkpoint` | auth | `CheckpointFormModel` |  |  | `auth.checkpoint.before` `auth.checkpoint.after` | code, recovery |
| `page.auth.login` | auth | `LoginFormModel` |  | `auth.login.form.after` | `auth.login.before` `auth.login.after` | default, invalid, submitting |
| `page.auth.password` | auth | `ForgotPasswordFormModel` |  |  | `auth.password.before` `auth.password.after` | default, invalid |
| `page.auth.passwordReset` | auth | `ResetPasswordFormModel` |  |  | `auth.passwordReset.before` `auth.passwordReset.after` | default, mismatch |
| `page.dashboard` | app | `PageStatusModel` |  |  |  | default, loading, error |
| `page.server.activity` | server | `ActivityPageModel` |  |  | `server.activity.before` `server.activity.after` | default |
| `page.server.backups` | server | `PageStatusModel` |  |  | `server.backups.before` `server.backups.after` | default, loading, error |
| `page.server.console` | server | `ServerConsoleModel` | `active: boolean` | `server.console.before` `server.console.after` |  | default, installing, maintenance |
| `page.server.databases` | server | `PageStatusModel` |  |  | `server.databases.before` `server.databases.after` | default, loading, error |
| `page.server.files` | server | `PageStatusModel` |  | `server.files.before` `server.files.after` |  | default, loading, error |
| `page.server.files.editor` | server | `FileEditorPageModel` |  |  | `server.files.editor.before` `server.files.editor.after` | file, new, loading, error |
| `page.server.network` | server | `PageStatusModel` |  |  | `server.network.before` `server.network.after` | default, loading, error |
| `page.server.schedules` | server | `PageStatusModel` |  |  | `server.schedules.before` `server.schedules.after` | default, loading, error |
| `page.server.schedules.detail` | server | `PageStatusModel` |  |  | `server.schedules.detail.before` `server.schedules.detail.after` | default, loading, error |
| `page.server.settings` | server | `ServerSettingsPageModel` |  |  | `server.settings.before` `server.settings.after` | default |
| `page.server.startup` | server | `StartupPageModel` |  | `server.startup.form` | `server.startup.before` `server.startup.after` | default, loading, error |
| `page.server.users` | server | `PageStatusModel` |  |  | `server.users.before` `server.users.after` | default, loading, error |
| `page.server.users.create` | server | `SubuserCreatePageModel` |  |  | `server.users.create.before` `server.users.create.after` | default, loading |

#### Blocks

| View | Context | Model | Inputs | Slots | Fixtures |
| --- | --- | --- | --- | --- | --- |
| `account.apiKeys.create` | account | `ApiKeyCreateModel` |  |  | default, invalid, created |
| `account.apiKeys.list` | account | `ApiKeyListModel` |  |  | keys, empty, loading, deleting |
| `account.avatar` | account | `AccountAvatarModel` |  |  | default, uploaded, picked |
| `account.email` | account | `AccountEmailModel` |  |  | default, submitting |
| `account.password` | account | `AccountPasswordModel` |  |  | default, invalid |
| `account.sshKeys.create` | account | `SshKeyCreateModel` |  |  | default, submitting |
| `account.sshKeys.list` | account | `SshKeyListModel` |  |  | keys, empty, loading |
| `account.twoFactor` | account | `AccountTwoFactorModel` |  |  | disabled, enabled, setup, setupLoading, disabling, recoveryTokens |
| `activity.log` | app | `ActivityLogModel` | `scope: ActivityScope` |  | account, server, filtered, empty, paged, loading, error |
| `app.search` | app | `AppSearchModel` |  |  | closed, results, loading, none, short, error |
| `dashboard.serverCard` | app | `ServerCardModel` | `server: Server` |  | running, offline, suspended, loading |
| `dashboard.serverCard.address` | app | `ServerCardModel` | `model: ServerCardModel`, `server: Server` |  |  |
| `dashboard.serverCard.identity` | app | `ServerCardModel` | `model: ServerCardModel`, `server: Server` | `dashboard.serverRow.name.after` |  |
| `dashboard.serverCard.metrics` | app | `ServerCardModel` | `model: ServerCardModel`, `server: Server` |  |  |
| `dashboard.serverList` | app | `ServerListModel` |  | `dashboard.before` `dashboard.after` `dashboard.serverRow.before` `dashboard.serverRow.metrics.after` `dashboard.serverRow.after` | servers, empty, loading |
| `server.allocations` | server | `ServerAllocationsModel` |  |  | allocations, full, unmanaged, readOnly, empty, loading |
| `server.backups` | server | `BackupListModel` |  |  | backups, empty, full, noLimit, paged, pastEnd, readOnly |
| `server.conflict` | server | `ServerConflictModel` |  |  | installing, suspended, maintenance, transferring, restoring |
| `server.databases` | server | `ServerDatabasesModel` |  |  | databases, full, empty, noLimit, readOnly, loading |
| `server.debug` | server | `ServerDebugModel` |  |  | default |
| `server.feature.eula` | server | `EulaPromptModel` |  |  | open, accepting |
| `server.feature.gslToken` | server | `GslTokenPromptModel` |  |  | open |
| `server.feature.hytaleOauth` | server | `HytaleOauthPromptModel` |  |  | open |
| `server.feature.javaVersion` | server | `JavaVersionPromptModel` |  |  | open, loading, noPermission |
| `server.feature.pidLimit` | server | `LimitPromptModel` |  |  | admin, user |
| `server.feature.steamDiskSpace` | server | `LimitPromptModel` |  |  | admin, user |
| `server.files.details` | server | `FileDetailsModel` | `file: FileObject` |  | file, directory |
| `server.files.editor` | server | `FileEditorModel` |  |  | file, dirty, saving, readOnly, pteroignore, new |
| `server.files.editor.actions` | server | `FileEditorModel` | `model: FileEditorModel` |  |  |
| `server.files.editor.canvas` | server | `FileEditorModel` | `model: FileEditorModel` |  |  |
| `server.files.editor.language` | server | `FileEditorModel` | `model: FileEditorModel` |  |  |
| `server.files.editor.notice` | server | `FileEditorModel` | `model: FileEditorModel` |  |  |
| `server.files.manager` | server | `FileManagerModel` |  |  | directory, selected, uploading, readOnly, empty, loading |
| `server.files.manager.list` | server | `FileManagerModel` | `model: FileManagerModel` | `server.files.rowActions` |  |
| `server.files.manager.selection` | server | `FileManagerModel` | `model: FileManagerModel` | `server.files.selectionActions` |  |
| `server.files.manager.toolbar` | server | `FileManagerModel` | `model: FileManagerModel` | `server.files.toolbar` |  |
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
<!-- /generated:views -->

### The full example

Aurora, in [`docs-samples/aurora`](docs-samples/aurora) of the panel repository, puts the levels together:
a glass sidebar shell, a split sign-in page, a card-grid dashboard with power buttons, the console with
power controls in the header, skins, settings and tests. The panel's checks compile it against this
package's types, run its tests and build it.

## Glossary

- **Area**: the account, a server or the admin area, each with a layout and navigation of its own.
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
- **Part**: a named element of a primitive (`root`, `title`).
- **Primitive**: a component of the UI kit, such as `Button` or `Dialog`, styled by skins.
- **Skin**: the recipes, by part, of one primitive.
- **Slot**: a named place where extensions mount content.
- **Thin layout**: an area layout that only places the page in `<Main>`, when `layout.app` draws the
  area's navigation.
- **Token**: a CSS custom property the panel's classes read, such as `--primary`.
- **View**: a named layout, page or block: an engine model and the stack of layers that render it.
