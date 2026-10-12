import { skin, type PrimitiveProps, type PrimitiveState, type ThemeSkins } from '@pterodactyl/sdk/theme';

// #region string
/** A string: the same classes in every state. It replaces classic's whole recipe, so it keeps z-9999. */
const tooltip = skin('Tooltip', {
    root: 'pointer-events-none z-9999 max-w-96 rounded-full bg-foreground px-3 py-1 text-xs text-background',
});
// #endregion string

// #region cva
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
// #endregion cva

// #region function
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
// #endregion function

// #region extend
/** Builds on the layers below: `below.root(state, extra)` is their classes for the part with `extra` merged in. */
const card = skin('Card', (below) => ({
    root: (state) => below.root(state, 'rounded-lg ring-1 ring-border/60'),
}));

/** Stat values in tabular figures, and labels in the body face, on top of classic's stats. */
const stat = skin('Stat', (below) => ({
    value: (state) => below.value(state, 'tabular-nums'),
    label: (state) => below.label(state, 'font-sans uppercase tracking-wide'),
}));
// #endregion extend

// #region component
/** A presentational primitive can be replaced by a component that takes the same props. */
function Banner({ type, title, className, children }: PrimitiveProps<'Alert'>) {
    return (
        <div role='alert' data-ui='alert' data-type={type} className={`rounded-xl border-l-4 p-4 ${className ?? ''}`}>
            {title && <p className='font-semibold'>{title}</p>}
            {children}
        </div>
    );
}
// #endregion component

// #region skins
export const skins = {
    Tooltip: tooltip,
    Badge: badge,
    Button: button,
    Card: card,
    Stat: stat,
    Alert: Banner,
} satisfies ThemeSkins;
// #endregion skins
