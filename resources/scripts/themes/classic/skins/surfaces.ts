import type { CompleteSkin } from '@/sdk/theme';

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

export const listRow = {
    root: {
        base: [
            'flex rounded-sm no-underline text-foreground items-center bg-card p-4 border border-transparent overflow-hidden',
            '[&_.icon]:flex [&_.icon]:w-16 [&_.icon]:items-center [&_.icon]:justify-center [&_.icon]:rounded-full [&_.icon]:bg-popover [&_.icon]:p-3',
        ].join(' '),
        variants: {
            interactive: {
                true: 'transition-[background-color,border-color,color,box-shadow] duration-150 hover:bg-popover hover:border-border active:bg-popover/80',
            },
        },
    },
} satisfies CompleteSkin<'ListRow'>;

export const pageHeading = {
    root: 'mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between',
    text: 'min-w-0',
    title: 'font-header text-2xl font-semibold tracking-normal text-foreground',
    description: 'mt-1 text-sm leading-relaxed text-foreground/70',
    actions: 'shrink-0',
} satisfies CompleteSkin<'PageHeading'>;
