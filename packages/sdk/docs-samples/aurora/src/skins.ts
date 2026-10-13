import { skin, type PrimitiveState, type ThemeSkins } from '@pterodactyl/sdk/theme';

// #region button
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
// #endregion button

// #region glass
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
// #endregion glass

// #region extend
/** Builds on the recipes below this layer by adding classes they do not set. */
const dialog = skin('Dialog', {
    backdrop: (state, below) => below('backdrop-blur-sm'),
    description: (state, below) => below('text-balance'),
});
// #endregion extend

export const auroraSkins: ThemeSkins = { Button: button, Dialog: dialog, ...glass };
