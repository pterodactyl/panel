import type { CompleteSkin } from '@/sdk/theme';

export const alert = {
    root: {
        base: 'flex items-start rounded-sm border border-l-4 px-4 py-3 text-foreground shadow-sm',
        variants: {
            type: {
                success: 'border-success bg-success/15',
                info: 'border-accent bg-accent/10',
                warning: 'border-warning bg-warning/15',
                danger: 'border-destructive bg-destructive/15',
            },
        },
    },
    icon: {
        base: 'mr-3 mt-0.5 h-5 w-5 shrink-0',
        variants: {
            type: {
                success: 'text-success',
                info: 'text-accent',
                warning: 'text-warning',
                danger: 'text-destructive',
            },
        },
    },
    body: 'min-w-0 flex-1 text-sm leading-relaxed',
    title: 'mb-1 font-semibold text-foreground',
    content: '',
} satisfies CompleteSkin<'Alert'>;

export const badge = {
    root: {
        base: 'rounded-sm text-xs',
        variants: {
            size: { sm: 'px-1.5 py-0.5', md: 'px-2 py-1' },
            variant: { soft: 'border' },
        },
        compoundVariants: [
            { variant: 'solid', tone: 'neutral', class: 'bg-popover text-foreground' },
            { variant: 'solid', tone: 'success', class: 'bg-success text-success-foreground' },
            { variant: 'solid', tone: 'warning', class: 'bg-warning text-warning-foreground' },
            { variant: 'solid', tone: 'danger', class: 'bg-destructive text-destructive-foreground' },
            { variant: 'solid', tone: 'info', class: 'bg-primary text-primary-foreground' },
            { variant: 'solid', tone: 'accent', class: 'bg-accent text-accent-foreground' },
            { variant: 'soft', tone: 'neutral', class: 'border-border bg-muted text-muted-foreground' },
            { variant: 'soft', tone: 'success', class: 'border-success/25 bg-success/10 text-success' },
            { variant: 'soft', tone: 'warning', class: 'border-warning/25 bg-warning/10 text-warning' },
            { variant: 'soft', tone: 'danger', class: 'border-destructive/25 bg-destructive/10 text-destructive' },
            { variant: 'soft', tone: 'info', class: 'border-primary/25 bg-primary/10 text-primary' },
            { variant: 'soft', tone: 'accent', class: 'border-accent/25 bg-accent/10 text-accent' },
        ],
    },
} satisfies CompleteSkin<'Badge'>;

export const empty = {
    root: {
        base: 'flex min-w-0 flex-1 flex-col items-center justify-center gap-6 rounded-sm border-dashed p-6 text-center text-balance md:p-12',
        variants: { size: { compact: 'gap-4 md:p-8' } },
    },
    header: 'flex max-w-sm flex-col items-center gap-2 text-center',
    media: {
        base: 'mb-2 flex shrink-0 items-center justify-center [&_svg]:pointer-events-none [&_svg]:shrink-0',
        variants: {
            variant: {
                default: 'bg-transparent',
                icon: "flex size-10 shrink-0 items-center justify-center rounded-sm bg-secondary text-secondary-foreground [&_svg:not([class*='size-'])]:size-6",
            },
        },
    },
    title: 'font-header text-lg font-medium tracking-tight text-foreground',
    description:
        'text-sm/relaxed text-muted-foreground [&>a]:underline [&>a]:underline-offset-4 [&>a:hover]:text-accent',
    content: 'flex w-full max-w-sm min-w-0 flex-col items-center gap-4 text-sm text-balance',
} satisfies CompleteSkin<'Empty'>;

export const progressBar = {
    root: {
        variants: {
            variant: {
                meter: 'h-2 w-full rounded-sm bg-sunken overflow-hidden',
                page: 'fixed h-0.5 w-full',
            },
        },
    },
    indicator: {
        variants: {
            variant: {
                meter: 'h-2 rounded-sm transition-[width,background-color] duration-150',
                page: 'h-full shadow-progress transition-[width] duration-300 ease-in-out',
            },
            tone: {
                neutral: 'bg-popover',
                success: 'bg-success',
                warning: 'bg-warning',
                danger: 'bg-destructive',
                info: 'bg-primary',
                accent: 'bg-accent',
            },
        },
    },
} satisfies CompleteSkin<'ProgressBar'>;

export const spinner = {
    root: {
        base: 'block rounded-full animate-[spin_1s_cubic-bezier(0.55,0.25,0.25,0.7)_infinite] border-muted border-t-foreground',
        variants: {
            blue: { true: 'border-ring border-t-primary' },
            size: {
                small: 'w-4 h-4 border-2',
                base: 'w-8 h-8 border-[3px]',
                large: 'w-16 h-16 border-[6px]',
            },
        },
    },
    container: {
        base: 'flex justify-center items-center',
        variants: { size: { small: 'm-6', base: 'm-6', large: 'm-20' } },
    },
} satisfies CompleteSkin<'Spinner'>;

export const spinnerOverlay = {
    root: {
        base: 'top-0 left-0 flex items-center justify-center w-full h-full rounded-sm flex-col z-40 bg-background/(--spinner-overlay-opacity)',
        variants: { fixed: { true: 'fixed', false: 'absolute' } },
    },
    text: 'mt-4 text-muted-foreground',
} satisfies CompleteSkin<'SpinnerOverlay'>;
