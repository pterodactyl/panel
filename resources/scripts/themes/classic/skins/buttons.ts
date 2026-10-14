import type { CompleteSkin } from '@/sdk/theme';

export const button = {
    root: {
        base: 'relative inline-block rounded-sm border p-2 text-sm uppercase tracking-wide transition-colors duration-150 disabled:cursor-default disabled:opacity-50',
        variants: {
            color: {
                primary:
                    'bg-primary border-primary text-primary-foreground hover:not-disabled:bg-primary/80 hover:not-disabled:border-primary',
                grey: 'border-border bg-popover text-foreground hover:not-disabled:bg-secondary hover:not-disabled:border-secondary',
                green: 'border-success bg-success text-success-foreground hover:not-disabled:bg-success/80 hover:not-disabled:border-success',
                red: 'border-destructive bg-destructive text-destructive-foreground hover:not-disabled:bg-destructive/80 hover:not-disabled:border-destructive',
                orange: 'border-caution bg-caution text-caution-foreground hover:not-disabled:bg-caution/80 hover:not-disabled:border-caution',
            },
            size: {
                xsmall: 'px-2 py-1 text-xs',
                small: 'px-4 py-2',
                large: 'p-4 text-sm',
                xlarge: 'p-4 w-full',
            },
            secondary: {
                true: 'border-border bg-transparent text-foreground hover:not-disabled:border-popover hover:not-disabled:bg-popover hover:not-disabled:text-foreground',
            },
        },
        compoundVariants: [
            {
                secondary: true,
                color: 'primary',
                class: 'hover:not-disabled:bg-primary hover:not-disabled:border-primary hover:not-disabled:text-primary-foreground',
            },
            {
                secondary: true,
                color: 'red',
                class: 'hover:not-disabled:bg-destructive hover:not-disabled:border-destructive hover:not-disabled:text-destructive-foreground',
            },
            {
                secondary: true,
                color: 'green',
                class: 'hover:not-disabled:bg-success hover:not-disabled:border-success hover:not-disabled:text-success-foreground',
            },
            {
                secondary: true,
                color: 'orange',
                class: 'hover:not-disabled:bg-caution hover:not-disabled:border-caution hover:not-disabled:text-caution-foreground',
            },
            {
                secondary: false,
                color: 'primary',
                class: 'active:not-disabled:bg-primary/70 active:not-disabled:border-primary/70',
            },
            {
                secondary: false,
                color: 'green',
                class: 'active:not-disabled:bg-success/70 active:not-disabled:border-success',
            },
            {
                secondary: false,
                color: 'red',
                class: 'active:not-disabled:bg-destructive/70 active:not-disabled:border-destructive',
            },
            {
                secondary: false,
                color: 'orange',
                class: 'active:not-disabled:bg-caution/70 active:not-disabled:border-caution',
            },
        ],
    },
    spinner: 'flex absolute justify-center items-center w-full h-full left-0 top-0',
    label: { base: 'inline-flex items-center gap-2', variants: { loading: { true: 'text-transparent' } } },
} satisfies CompleteSkin<'Button'>;

export const newButton = {
    root: 'inline-flex h-9 shrink-0 items-center justify-center gap-2 px-3 normal-case font-medium tracking-normal',
    content: 'inline-flex items-center gap-2',
    icon: 'h-4 w-4',
} satisfies CompleteSkin<'NewButton'>;
