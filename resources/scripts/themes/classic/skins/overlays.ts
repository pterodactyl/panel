import type { CompleteSkin } from '@/sdk/theme';

export const dialog = {
    backdrop: [
        'fixed inset-0 bg-background/50 z-40 transition-opacity duration-150 ease-out',
        'data-starting-style:opacity-0 data-ending-style:opacity-0 data-ending-style:ease-in',
    ].join(' '),
    viewport: 'fixed inset-0 overflow-y-auto z-50',
    container: 'flex min-h-full items-center justify-center p-4 text-center',
    root: [
        'relative flex flex-col overflow-hidden bg-card rounded-sm max-w-xl w-full mx-auto shadow-lg text-left',
        'max-h-[calc(100dvh-2rem)] ring-4 ring-border/80 transition duration-150 ease-out',
        'data-starting-style:opacity-0 data-starting-style:scale-75',
        'data-ending-style:opacity-0 data-ending-style:scale-75 data-ending-style:ease-in',
    ].join(' '),
    body: 'flex min-h-0 flex-1 overflow-y-auto p-6 pb-0',
    content: 'flex-1 min-w-0',
    header: 'flex items-center',
    heading: '',
    title: {
        base: 'font-header text-xl font-semibold tracking-normal text-foreground pr-4',
        variants: { described: { true: 'mb-2', false: 'mb-5' } },
    },
    description: 'mb-5 text-sm leading-relaxed text-muted-foreground',
    spacer: 'invisible h-6',
    footer: 'px-6 py-3 bg-card flex items-center justify-end space-x-3 rounded-b-sm',
    close: 'absolute right-0 top-0 m-4',
    closeButton: 'group w-8 h-8 p-0 inline-flex items-center justify-center',
    closeIcon: 'w-5 h-5 group-hover:rotate-90 transition-transform duration-100',
    icon: {
        base: 'flex items-center justify-center w-10 h-10 rounded-full mr-4',
        variants: {
            type: {
                danger: 'bg-destructive text-destructive-foreground',
                warning: 'bg-warning text-warning-foreground',
                success: 'bg-success text-success-foreground',
                info: 'bg-primary text-primary-foreground',
            },
        },
    },
    iconGlyph: 'w-6 h-6',
} satisfies CompleteSkin<'Dialog'>;

export const tooltip = {
    root: 'bg-popover text-sm text-popover-foreground px-3 py-2 rounded-sm pointer-events-none max-w-[24rem] z-9999',
    arrow: {
        base: 'absolute bg-popover w-3 h-3',
        variants: {
            side: {
                top: 'bottom-[-6px] left-0',
                bottom: 'top-[-6px] left-0',
                right: 'top-0 left-[-6px]',
                left: 'top-0 right-[-6px]',
            },
        },
    },
} satisfies CompleteSkin<'Tooltip'>;

export const dropdownMenu = {
    trigger: '',
    positioner: 'z-9999 outline-hidden',
    root: [
        'min-w-56 bg-popover border border-border rounded-sm shadow-xl p-1.5 text-sm text-popover-foreground',
        'outline-hidden origin-(--transform-origin) transition duration-100',
        'data-starting-style:opacity-0 data-starting-style:scale-95',
        'data-ending-style:opacity-0 data-ending-style:scale-95',
    ].join(' '),
} satisfies CompleteSkin<'DropdownMenu'>;

export const dropdownMenuItem = {
    root: {
        base: [
            'group flex w-full gap-3 px-2.5 py-2.5 rounded-sm cursor-pointer select-none outline-hidden',
            'text-foreground transition-colors data-highlighted:bg-muted data-highlighted:text-foreground',
        ].join(' '),
        variants: {
            described: { true: 'items-start', false: 'items-center' },
            danger: { true: 'text-destructive data-highlighted:bg-destructive/10 data-highlighted:text-destructive' },
        },
    },
    icon: {
        base: [
            'flex h-7 w-7 shrink-0 items-center justify-center rounded-sm border border-border bg-muted text-muted-foreground transition-colors',
            'group-data-highlighted:border-border group-data-highlighted:bg-background group-data-highlighted:text-foreground',
        ].join(' '),
        variants: {
            described: { true: 'mt-0.5' },
            danger: {
                true: 'border-destructive/30 bg-destructive/10 text-destructive group-data-highlighted:border-destructive/40 group-data-highlighted:bg-destructive/15 group-data-highlighted:text-destructive',
            },
        },
    },
    glyph: 'h-4 w-4',
    text: 'flex min-w-0 flex-1 flex-col',
    label: 'truncate text-sm font-medium leading-5',
    description: {
        base: 'mt-0.5 text-xs leading-4 text-muted-foreground group-data-highlighted:text-muted-foreground',
        variants: { danger: { true: 'group-data-highlighted:text-destructive/80' } },
    },
} satisfies CompleteSkin<'DropdownMenuItem'>;
