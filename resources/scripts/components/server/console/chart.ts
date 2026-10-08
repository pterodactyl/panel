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

/** A performance.now() clock that re-renders at ~`fps` while `active`. */
export function useAnimationClock(active: boolean, fps = 30): number {
    const [now, setNow] = useState(() => performance.now());

    useEffect(() => {
        if (!active) {
            return;
        }

        const minDelta = 1000 / fps;
        let frame = 0;
        let last = 0;

        const tick = (timestamp: number) => {
            frame = requestAnimationFrame(tick);
            if (timestamp - last >= minDelta) {
                last = timestamp;
                setNow(timestamp);
            }
        };

        frame = requestAnimationFrame(tick);

        return () => cancelAnimationFrame(frame);
    }, [active, fps]);

    return now;
}

/** Whether the element intersects the viewport while the document is visible. */
export function useElementVisible(ref: RefObject<Element | null>): boolean {
    const [intersecting, setIntersecting] = useState(false);
    const [documentVisible, setDocumentVisible] = useState(() => document.visibilityState !== 'hidden');

    useEffect(() => {
        const element = ref.current;

        if (!element) {
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            const entry = entries.at(-1);

            if (entry) {
                setIntersecting(entry.isIntersecting);
            }
        });

        observer.observe(element);

        return () => observer.disconnect();
    }, [ref]);

    useEffect(() => {
        const update = () => setDocumentVisible(document.visibilityState !== 'hidden');

        document.addEventListener('visibilitychange', update);

        return () => document.removeEventListener('visibilitychange', update);
    }, []);

    return intersecting && documentVisible;
}
