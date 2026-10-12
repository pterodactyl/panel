import type { CompleteSkin } from '@/sdk/theme';

const control = {
    base: [
        'resize-none appearance-none outline-hidden w-full min-w-0',
        'p-3 border-2 rounded-sm text-sm transition-[background-color,border-color,color,box-shadow] duration-150',
        'bg-input border-input hover:border-input text-foreground shadow-none focus:ring-0',
        'focus:shadow-md focus:border-primary focus:ring-2 focus:ring-ring/50',
        'disabled:opacity-75 read-only:cursor-text read-only:bg-muted/40',
        'required:shadow-none invalid:shadow-none',
    ].join(' '),
    variants: {
        light: {
            true: 'bg-background border-border text-foreground focus:border-primary disabled:bg-muted disabled:border-border',
        },
        error: {
            true: 'text-destructive border-destructive hover:border-destructive focus:border-destructive focus:ring-destructive',
        },
    },
} satisfies CompleteSkin<'Input'>['root'];

export const input = { root: control } satisfies CompleteSkin<'Input'>;
export const textArea = { root: control } satisfies CompleteSkin<'TextArea'>;
export const numberInput = { root: '', input: control } satisfies CompleteSkin<'NumberInput'>;

export const fileInput = {
    root: [
        'block w-full min-w-0 rounded-sm text-sm text-foreground',
        'file:mr-3 file:rounded-sm file:border file:border-border file:bg-popover file:px-4 file:py-2 file:text-sm file:font-medium file:text-foreground',
        'enabled:cursor-pointer enabled:file:cursor-pointer enabled:hover:file:bg-secondary',
        'focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-card',
        'disabled:cursor-default disabled:opacity-50',
    ].join(' '),
} satisfies CompleteSkin<'FileInput'>;

export const select = {
    root: {
        base: [
            'relative flex items-center w-full rounded-sm border text-sm transition-colors duration-150',
            'bg-input border-input text-foreground hover:border-input data-popup-open:border-input',
            'focus-within:shadow-md focus-within:border-primary focus-within:ring-2 focus-within:ring-ring/50',
            'data-disabled:opacity-75 data-disabled:cursor-default',
        ].join(' '),
        variants: {
            error: {
                true: 'border-destructive text-destructive focus-within:border-destructive focus-within:ring-destructive',
            },
        },
    },
    input: {
        base: [
            'w-full min-w-0 appearance-none border-0 bg-transparent p-3 pr-10 text-sm text-foreground outline-hidden',
            'placeholder:text-muted-foreground focus:ring-0 focus:outline-hidden disabled:cursor-default',
        ].join(' '),
        compoundVariants: [
            { multiple: true, selected: true, class: 'pl-0' },
            { richLabel: true, class: 'absolute inset-0 h-full' },
            { richLabel: true, searching: false, class: 'text-transparent' },
        ],
    },
    trigger:
        'absolute right-0 top-0 flex h-full w-10 items-center justify-center border-0 bg-transparent p-0 text-muted-foreground [&_svg]:h-4 [&_svg]:w-4 [&_svg]:shrink-0',
    positioner: 'z-99999 min-w-(--anchor-width) outline-none',
    popup: [
        'bg-card border border-border rounded-sm shadow-lg py-1 text-sm text-foreground',
        'w-(--anchor-width) max-w-(--available-width) max-h-[min(18rem,var(--available-height))]',
    ].join(' '),
    list: 'max-h-72 overflow-y-auto overscroll-contain outline-none',
    item: 'flex items-center justify-between px-3 py-2 cursor-pointer select-none outline-hidden data-highlighted:bg-popover data-highlighted:text-foreground data-selected:text-accent data-disabled:opacity-50 data-disabled:cursor-default',
    itemLabel: 'min-w-0',
    indicator: '',
    empty: 'px-3 py-2 text-sm text-muted-foreground empty:p-0',
    group: '',
    groupLabel: 'px-3 pt-2 pb-1 text-xs uppercase text-muted-foreground empty:hidden',
    summary: 'pointer-events-none whitespace-nowrap px-3 py-3 text-sm text-foreground',
    preview: {
        base: 'pointer-events-none flex min-w-0 flex-1 items-center px-3 py-2 pr-10 text-sm',
        variants: { searching: { true: 'invisible' } },
    },
} satisfies CompleteSkin<'Select'>;

export const checkbox = {
    root: [
        'flex items-center justify-center shrink-0 w-4 h-4 rounded-xs cursor-pointer',
        'bg-input border border-input text-primary-foreground transition-colors duration-75 hover:border-primary/70',
        'data-checked:bg-primary data-checked:border-transparent',
        'data-indeterminate:bg-primary data-indeterminate:border-transparent',
        'data-disabled:opacity-60 data-disabled:cursor-default',
        '[&_svg]:size-3.5',
    ].join(' '),
    indicator: '',
} satisfies CompleteSkin<'Checkbox'>;

export const switchSkin = {
    root: { base: 'flex gap-4', variants: { described: { true: 'items-start', false: 'items-center' } } },
    control: [
        'group relative inline-block shrink-0 w-12 h-6 rounded-full border cursor-pointer select-none p-0',
        'bg-input border-input transition-colors duration-75',
        'hover:border-primary/70 active:bg-popover data-checked:bg-primary data-checked:border-primary',
        'data-disabled:opacity-60 data-disabled:cursor-default',
    ].join(' '),
    thumb: [
        'block absolute top-1/2 left-0.5 size-4.5 -translate-y-1/2 rounded-full border bg-background',
        'transition-[left] duration-75 ease-in',
        'group-data-checked:left-[calc(100%_-_1.125rem_-_0.125rem)]',
        'group-data-checked:bg-primary-foreground group-data-checked:border-primary-foreground',
    ].join(' '),
    text: 'min-w-0 flex-1',
    label: 'cursor-pointer mb-0',
    description: 'input-help',
} satisfies CompleteSkin<'Switch'>;

export const label = {
    root: {
        base: 'block text-sm font-medium uppercase text-foreground mb-1',
        variants: { light: { true: 'text-muted-foreground' } },
    },
} satisfies CompleteSkin<'Label'>;
