import { useLayoutEffect, useState, type ReactNode } from 'react';
import type { WorkbenchWidth } from '@/router/search';
import { cn } from '@/lib/cn';

export const VIEWPORT_WIDTHS = {
    phone: 390,
    tablet: 820,
    desktop: 1280,
    full: null,
} satisfies Record<WorkbenchWidth, number | null>;

export interface FrameSize {
    readonly width: number;
    /** Below 1 when the frame is shrunk to fit the space available. */
    readonly scale: number;
}

/** The width of an element, kept current. */
export function useWidth(element: HTMLElement | null): number {
    const [width, setWidth] = useState(0);

    useLayoutEffect(() => {
        if (!element) {
            return;
        }

        const observer = new ResizeObserver(([entry]) => setWidth(Math.floor(entry.contentRect.width)));

        // Observations wait for rendering, which a hidden tab skips.
        setWidth(Math.floor(element.getBoundingClientRect().width));
        observer.observe(element);

        return () => observer.disconnect();
    }, [element]);

    return width;
}

/** The frame width for a preset, and the scale that fits it into the space available. */
export function frameSize(preset: WorkbenchWidth, available: number): FrameSize {
    const width = VIEWPORT_WIDTHS[preset] ?? available;

    return { width: Math.max(width, 1), scale: available > 0 && width > available ? available / width : 1 };
}

export function Segmented<T extends string>({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: T;
    options: readonly { value: T; label: ReactNode; disabled?: boolean; title?: string }[];
    onChange: (value: T) => void;
}) {
    return (
        <div
            role='group'
            aria-label={label}
            className='inline-flex rounded-sm border border-border bg-background p-0.5'
        >
            {options.map((option) => (
                <button
                    key={option.value}
                    type='button'
                    aria-pressed={option.value === value}
                    disabled={option.disabled}
                    title={option.title}
                    className={cn(
                        'rounded-xs px-2.5 py-1 text-xs font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-40',
                        option.value === value
                            ? 'bg-popover text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    )}
                    onClick={() => onChange(option.value)}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}
