import { useCallback, useEffect, useState, type RefObject } from 'react';

export const CHART_WINDOW_MS = 20_000;

const RETAIN_MS = CHART_WINDOW_MS + 2_000;

// `t` is a performance.now() timestamp; a series value is null until its first sample.
export type ChartPoint = { t: number } & Record<string, number | null>;

/** Rolling buffer of timestamped samples; `keys` must be referentially stable. */
export function useRollingData(keys: readonly string[]) {
    const [data, setData] = useState<ChartPoint[]>([]);

    const push = useCallback(
        (values: Record<string, number | null>, t: number) =>
            setData((prev) => {
                const point: ChartPoint = { t };

                for (const key of keys) {
                    const value = values[key];

                    point[key] = value === null || value === undefined ? null : Number(value.toFixed(2));
                }

                const cutoff = t - RETAIN_MS;
                let start = 0;

                while (start < prev.length && prev[start].t < cutoff) {
                    start++;
                }

                return [...prev.slice(start), point];
            }),
        [keys]
    );

    const clear = useCallback(() => setData([]), []);

    return { data, push, clear };
}

/** The element's width in whole pixels; 0 until it has been measured. */
export function useElementWidth(ref: RefObject<Element | null>): number {
    const [width, setWidth] = useState(0);

    useEffect(() => {
        const element = ref.current;

        if (!element) {
            return;
        }

        const observer = new ResizeObserver((entries) => {
            const entry = entries.at(-1);

            if (entry) {
                setWidth(Math.floor(entry.contentRect.width));
            }
        });

        observer.observe(element);

        return () => observer.disconnect();
    }, [ref]);

    return width;
}
