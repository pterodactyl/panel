import { useEffect, useState, type RefObject } from 'react';

export const CHART_WINDOW_MS = 20_000;

const RETAIN_MS = CHART_WINDOW_MS + 2_000;

// `t` is a performance.now() timestamp; a series value is null until its first sample.
export type ChartPoint = { t: number } & Record<string, number | null>;

// Type aliases, not interfaces, so a chart reads them as `ChartPoint`s.
export type ServerGraphPoint = { readonly t: number; readonly value: number | null };
export type ServerNetworkPoint = { readonly t: number; readonly rx: number | null; readonly tx: number | null };
export interface ServerGraphs {
    /** CPU load in percent. */
    readonly cpu: readonly ServerGraphPoint[];
    /** Memory in MiB. */
    readonly memory: readonly ServerGraphPoint[];
    /** Bytes received and sent since the previous sample. */
    readonly network: readonly ServerNetworkPoint[];
}

/** A sample value as the graphs plot it. */
export const chartValue = (value: number): number => Number(value.toFixed(2));

/** Appends a sample to a rolling buffer, dropping samples older than the chart window keeps. */
export function appendChartPoint<TPoint extends { readonly t: number }>(
    points: readonly TPoint[],
    point: TPoint
): TPoint[] {
    const cutoff = point.t - RETAIN_MS;
    let start = 0;

    while (start < points.length && points[start].t < cutoff) {
        start++;
    }

    return [...points.slice(start), point];
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
