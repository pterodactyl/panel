import type { CompleteSkin } from '@/sdk/theme';

const fading = { fetching: { true: 'opacity-55' } };

export const table = {
    root: '@container overflow-hidden rounded-sm border border-border bg-card',
    empty: { base: 'flex transition-opacity', variants: fading },
    scroller: 'overflow-x-auto scrollbar-thin scrollbar-thumb-(--scrollbar-thumb)',
    table: 'w-full border-collapse text-left text-sm',
    head: 'border-b border-border bg-muted/70',
    headerRow: '',
    headerCell: 'h-10 whitespace-nowrap px-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground',
    body: { base: 'divide-y divide-border transition-opacity', variants: fading },
    row: 'transition-colors hover:bg-muted/35',
    cell: 'px-3 py-2.5 align-middle text-foreground',
} satisfies CompleteSkin<'Table'>;

export const pagination = {
    root: 'mt-4 flex justify-center',
    page: 'p-0 w-10 h-10 not-last-of-type:mr-2',
} satisfies CompleteSkin<'Pagination'>;

export const code = {
    root: {
        base: 'font-mono px-1.5 py-0.5 inline-block rounded-sm',
        variants: { dark: { true: 'bg-muted text-foreground', false: 'bg-sunken' } },
    },
} satisfies CompleteSkin<'Code'>;

export const copyOnClick = {
    root: 'fixed z-50 bottom-0 right-0 m-4',
    status: 'rounded-md py-3 px-4 text-foreground bg-popover/95 shadow-sm',
    message: '',
    target: 'cursor-pointer',
} satisfies CompleteSkin<'CopyOnClick'>;

export const avatar = { root: '' } satisfies CompleteSkin<'Avatar'>;
