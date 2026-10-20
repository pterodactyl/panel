import type { CompleteSkin, UiTone } from '@/sdk/theme';

const fading = { fetching: { true: 'opacity-55' } };

export const table = {
    root: '@container relative overflow-hidden rounded-sm border border-border bg-card',
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

/** A tile's icon box and mobile bar by tone, and the icon on it. */
const tileFill = {
    neutral: 'bg-card',
    success: 'bg-success',
    warning: 'bg-warning',
    danger: 'bg-destructive',
    info: 'bg-primary',
    accent: 'bg-accent',
} satisfies Record<UiTone, string>;
const tileInk = {
    neutral: 'text-foreground',
    success: 'text-success-foreground',
    warning: 'text-warning-foreground',
    danger: 'text-destructive-foreground',
    info: 'text-primary-foreground',
    accent: 'text-accent-foreground',
} satisfies Record<UiTone, string>;
/** An inline stat colours its icon and value. */
const inlineInk = {
    neutral: 'text-muted-foreground',
    success: 'text-success',
    warning: 'text-warning',
    danger: 'text-destructive',
    info: 'text-primary',
    accent: 'text-accent',
} satisfies Record<UiTone, string>;

export const stat = {
    root: {
        variants: {
            variant: {
                tile: 'relative flex items-center rounded-sm bg-popover px-3 py-2 shadow-lg md:p-3 lg:p-4',
                // The icon and value share a centred line and the limit takes the next.
                inline: 'flex flex-wrap justify-center',
            },
        },
    },
    indicator: ({ variant, tone }) =>
        variant === 'tile' ? `absolute top-0 left-0 h-full w-1 rounded-l-sm sm:hidden ${tileFill[tone]}` : 'hidden',
    icon: ({ variant, tone }) =>
        variant === 'tile'
            ? `hidden h-12 w-12 shrink-0 items-center justify-center rounded-lg shadow-md transition-colors duration-500 sm:mr-4 sm:flex ${tileFill[tone]}`
            : 'flex',
    glyph: ({ variant, tone }) =>
        variant === 'tile' ? `m-auto size-6 ${tileInk[tone]}` : `size-[1em] shrink-0 ${inlineInk[tone]}`,
    body: {
        variants: {
            variant: {
                tile: 'flex w-full flex-wrap content-center items-baseline overflow-hidden',
                // Its parts join the root's lines.
                inline: 'contents',
            },
        },
    },
    label: {
        variants: {
            variant: {
                tile: 'basis-full font-header font-medium text-xs leading-tight md:text-sm text-foreground',
                inline: 'sr-only',
            },
        },
    },
    value: ({ variant, tone, unavailable }) =>
        variant === 'tile'
            ? `h-7 min-w-0 truncate text-lg font-semibold leading-7 md:text-xl ${unavailable ? 'text-muted-foreground' : 'text-foreground'}`
            : `ml-2 text-sm ${unavailable ? 'text-muted-foreground' : inlineInk[tone]}`,
    limit: {
        variants: {
            variant: {
                tile: 'ml-1 shrink-0 select-none text-xs text-muted-foreground',
                inline: 'mt-1 basis-full text-center text-xs text-muted-foreground',
            },
        },
    },
} satisfies CompleteSkin<'Stat'>;

export const chartCard = {
    root: 'group relative rounded-sm bg-popover pt-2 shadow-lg border-b-4 border-border',
    header: 'flex items-center justify-between px-4 py-2',
    title: 'font-header font-medium transition-colors duration-100 group-hover:text-foreground',
    legend: 'text-sm flex items-center',
    body: 'z-10 ml-2',
} satisfies CompleteSkin<'ChartCard'>;
