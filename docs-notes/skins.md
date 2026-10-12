# Skin reference (data for the docs lane)

Source of truth: `resources/scripts/themes/primitives.ts` (`UiPrimitives`, `UI_PRIMITIVES`), the registry in
`resources/scripts/themes/skins.ts`, classic's recipes in `resources/scripts/themes/classic/skins/*.ts`.

## API (`@pterodactyl/sdk/theme`)

- `defineTheme({ ui })`: `ui` is `ThemeSkins`, keyed by primitive name.
- `skin(name, skin)` types a skin; `skin(name, (parent) => skin)` extends the layers below. The function form must go
  through `skin()`: a bare function is read as a replacement component.
- Skin: `{ [part]: recipe }`. A recipe is a string, a cva recipe `{ base, variants, compoundVariants, defaultVariants }`,
  or `(state) => string`. Variant keys are state keys; booleans use `'true'`/`'false'`.
- `parent` in the function form has every part resolved: `parent.root(state)` returns the classes below.
- `useSkin(name, state)` returns a binding: `ui(part, className?, partState?)` gives
  `{ className, 'data-ui', 'data-part', data-* }` to spread; `ui.classes(part, className?, partState?)` gives the string.
- Types: `ThemeSkins`, `Skin<N>`, `CompleteSkin<N>`, `CompleteSkins`, `SkinRecipe<S>`, `SkinRecipeConfig<S>`,
  `SkinCompoundVariant<S>`, `ResolvedSkin<N>`, `SkinExtension<N>`, `SkinDefinition<N>`, `SkinBinding<N>`,
  `SkinPartProps`, `SkinClassName`, `SkinPart<N>`, `PrimitiveName`, `PrimitiveState<N>`, `PrimitiveProps<N>`,
  `ReplaceableName`, `UiPrimitives`, `UiTone`, `NoState`, `SkinStateMap`, `SkinStateValue`, `PrimitiveMeta<N>`;
  value `UI_PRIMITIVES` (parts and `replaceable` per primitive).

## Rules

- Layers: classic, then the theme chain parent first. For each part the topmost recipe wins and replaces the one below
  wholesale; nothing merges. Parts a skin leaves out keep the recipe below.
- The call-site `className` is appended with `cn()` (tailwind-merge), so it wins conflicts.
- Every part renders `data-ui="<kebab-name>"` and `data-part="<part>"`. The root also renders a `data-<key>` per state
  value (camelCase keys become kebab case: `richLabel` → `data-rich-label`). `true` renders as an empty attribute,
  `false`/unset render nothing. A part given its own state (Dialog `icon`, Empty `media`, Pagination `page`) renders
  that state on itself.
- Parts marked "classes only" below are passed as `className` to another primitive or element and carry no attributes.
- Presentational primitives can be replaced by a component receiving the identical props. The topmost replacement wins;
  skins of all layers still resolve for the core components. A replacement must not render the primitive it replaces.
- Base UI primitives (and the other skin-only ones) cannot be replaced: a component there is a type error, and at
  runtime it is ignored with a dev warning. Unknown primitive names and unknown parts are ignored with a dev warning.
- Resolution is memoised per primitive per layer stack; a recipe or extension that throws is skipped and logged.

## Primitives

`root` gets the bound state as `data-*`. Replaceable = may be a component.

| Primitive | Parts | State (→ `data-*`) | Replaceable |
|---|---|---|---|
| Alert | root, icon, body, title, content | `type`: success, info, warning, danger | yes |
| Avatar | root | `variant`: beam, marble, pixel, sunset, ring, bauhaus | yes |
| Badge | root | `tone`: neutral, success, warning, danger, info, accent; `size`: sm, md; `variant`: solid, soft | yes |
| Button | root, spinner, label | `color`: primary, grey, green, red, orange; `size`: xsmall, small, large, xlarge; `secondary`; `loading` | yes |
| Card | root, header, title, icon, actions, body | `titled` (has a header); `actions`; `loading` | yes |
| Checkbox | root, indicator | none (Base UI sets `data-checked`, `data-indeterminate`, `data-disabled`) | no |
| Code | root | `dark` | no |
| CopyOnClick | root (toast), status, message, target (classes only, added to the clicked child) | `status`: copied, failed | no |
| Dialog | backdrop, viewport, container, root (popup), body, content, header, heading, title, description, spacer, footer, close, closeButton (classes only, on a Button), closeIcon, icon, iconGlyph | `described`; `type` on `icon`: danger, warning, success, info | no |
| DropdownMenu | trigger, positioner, root (popup) | `context` (ContextDropdownMenu) | no |
| DropdownMenuItem | root, icon, glyph, text, label, description | `danger`; `described` | no |
| Empty | root, header, media, title, description, content | `size`: default, compact; `variant` on `media`: default, icon | yes (root) |
| FileInput | root | none | no |
| Input | root | `light`; `error` | no |
| Label | root | `light` | no |
| ListRow | root | `interactive` | yes |
| NewButton | root (classes only, on the Button), content, icon | `link` (NewLinkButton) | no |
| NumberInput | root (NumberField), input | `light`; `error` | no |
| PageHeading | root, text, title, description, actions | none | yes |
| Pagination | root, page (classes only, on each page Button) | `current` on `page` | no |
| ProgressBar | root (track), indicator | `variant`: meter, page; `tone`: as Badge | yes |
| Select | root (input group), input, trigger, positioner, popup, list, item, itemLabel, indicator, empty, group, groupLabel, summary, preview | `error`; `multiple`; `selected`; `richLabel`; `searching` | no |
| Spinner | root, container (when centered) | `size`: small, base, large; `blue`; `centered` | yes |
| SpinnerOverlay | root, text | `fixed` | no |
| Switch | root, control, thumb, text, label (classes only, on the Label), description | `described`; `position`: start, end | no |
| Table | root, empty, scroller, table, head, headerRow, headerCell, body, row, cell | `fetching` | no |
| TextArea | root | `light`; `error` | no |
| Tooltip | root, arrow | `side`: top, right, bottom, left; `arrow` | no |

## Components and props (`@pterodactyl/sdk`)

Button (`ButtonProps`, `ButtonColor`, `ButtonSize`; `Button.Text`, `Button.Danger`), LinkButton, RouterLinkButton,
NewButton/NewLinkButton (`NewButtonProps`, `NewLinkButtonProps`), Input (`InputProps`), TextArea (`TextAreaProps`),
NumberInput (`NumberInputProps`), FileInput (`FileInputProps`), Select (`SelectProps`), Checkbox (`CheckboxProps`),
Switch (`SwitchProps`), Label (`LabelProps`), Dialog (`DialogProps`, `DialogIconProps`; `Dialog.Footer`, `Dialog.Icon`,
`Dialog.Confirm`), Alert (`AlertProps`, `AlertType`), Tooltip (`TooltipProps`), DropdownMenu / DropdownMenuItem /
ContextDropdownMenu (`DropdownMenuProps`, `DropdownMenuItemProps`, `ContextDropdownMenuProps`), Table (`TableProps`),
Pagination (`PaginationProps`), Empty + EmptyHeader/EmptyMedia/EmptyTitle/EmptyDescription/EmptyContent (`EmptyProps`,
`EmptySize`, `EmptyMediaVariant`), Spinner (`SpinnerProps`), SpinnerOverlay (`SpinnerOverlayProps`), Avatar
(`AvatarProps`), ProgressBar (`ProgressBarProps`), Code (`CodeProps`), CopyOnClick (`CopyOnClickProps`), Card
(`CardProps`), ListRow (`ListRowProps`), Badge (`BadgeProps`), PageHeading (`PageHeadingProps`). ContentBox and
TitledGreyBox remain as thin wrappers over Card.

## Recipes

```tsx
export default defineTheme({
    ui: {
        // Replaces Button's root recipe; spinner and label keep classic's.
        Button: skin('Button', {
            root: {
                base: 'inline-flex h-9 items-center rounded-full px-4 font-medium',
                variants: { color: { primary: 'bg-primary text-primary-foreground hover:bg-primary/90' } },
                compoundVariants: [{ secondary: true, color: 'primary', class: 'bg-transparent text-primary' }],
            },
        }),
        // Builds on the classes below.
        Card: skin('Card', (parent) => ({ root: (state) => `${parent.root(state)} backdrop-blur-xl bg-card/60` })),
        // Presentational primitives can be components with the same props.
        Badge: AuroraBadge,
    },
});
```

Classic's normalised variants: plain `hover:`/`active:` guarded with `not-disabled:` on Button
(`hover:not-disabled:bg-primary/80`), `data-checked:`/`data-highlighted:`/`data-starting-style:` instead of
`data-[…]:`, `z-9999`, and `(--var)` shorthands. Rendered classes are otherwise unchanged
(`resources/scripts/themes/classicClasses.spec.tsx`).
