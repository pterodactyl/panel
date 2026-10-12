import type { CompleteSkin, UiTone } from '@/sdk/theme';

export const card = {
    root: {
        base: 'bg-card rounded-sm',
        variants: {
            titled: { true: 'shadow-md', false: 'p-4 shadow-lg relative' },
            loading: { true: 'relative' },
        },
    },
    header: {
        base: 'bg-muted rounded-t-sm p-3 border-b border-border',
        variants: { actions: { true: 'flex items-center justify-between gap-3' } },
    },
    title: 'font-header text-sm font-semibold uppercase tracking-wide text-foreground',
    icon: 'mr-2 text-muted-foreground',
    actions: 'flex shrink-0 items-center gap-2',
    body: 'p-3',
} satisfies CompleteSkin<'Card'>;

const statusFills = {
    neutral: 'bg-popover',
    success: 'bg-success',
    warning: 'bg-warning',
    danger: 'bg-destructive',
    info: 'bg-primary',
    accent: 'bg-accent',
} satisfies Record<UiTone, string>;

export const listRow = {
    root: {
        base: 'group relative flex rounded-sm no-underline text-foreground items-center bg-card p-4 border border-transparent overflow-hidden',
        variants: {
            interactive: {
                true: 'transition-[background-color,border-color,color,box-shadow] duration-150 hover:bg-popover hover:border-border active:bg-popover/80',
            },
        },
    },
    icon: 'flex w-16 items-center justify-center rounded-full bg-popover p-3',
    // A bar down the row's right edge.
    status: ({ status }) =>
        `absolute right-0 z-20 m-1 h-[calc(100%-0.5rem)] w-2 rounded-full opacity-50 transition-opacity duration-150 group-hover:opacity-75 ${statusFills[status ?? 'neutral']}`,
} satisfies CompleteSkin<'ListRow'>;

export const pageFooter = {
    root: 'page-width mb-4',
    text: 'text-center text-muted-foreground text-xs',
    link: 'no-underline text-muted-foreground hover:text-muted-foreground',
} satisfies CompleteSkin<'PageFooter'>;

export const pageHeading = {
    root: 'mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between',
    text: 'min-w-0',
    title: 'font-header text-2xl font-semibold tracking-normal text-foreground',
    description: 'mt-1 text-sm leading-relaxed text-foreground/70',
    actions: 'shrink-0',
} satisfies CompleteSkin<'PageHeading'>;
