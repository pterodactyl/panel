import { lazy, Suspense, useId, useLayoutEffect, useMemo, useRef } from 'react';
import { type ChartPoint, useElementWidth } from '@/components/server/console/chart';
import type { ChartSeries } from '@/components/server/console/types';

const GRID_COLOR = 'var(--border)';
const TICK_COLOR = 'var(--muted-foreground)';
const SANS_FONT = 'var(--font-sans)';

const Y_AXIS_WIDTH = 64;
const MARGIN = { top: 8, right: 8, bottom: 0, left: 0 };

// How far the visible right edge trails real time; just over the ~1s stats cadence.
const RIGHT_LAG_MS = 1100;

// Time drawn past the visible right edge, so the plot can keep sliding while the next sample is late.
const SLIDE_MS = 4000;

interface Props {
    data: ChartPoint[];
    series: ChartSeries[];
    live: boolean;
    windowMs: number;
    // Y-axis maximum that grows when the data exceeds it; omit to auto-scale.
    suggestedMax?: number;
    tickFormatter?: (value: number) => string;
    height?: number;
}

type YDomain = [number, (dataMax: number) => number] | [number, string];

interface RechartsAreaChartContentProps {
    data: ChartPoint[];
    series: ChartSeries[];
    live: boolean;
    uid: string;
    width: number;
    height: number;
    windowMs: number;
    yDomain: YDomain;
    tickFormatter?: (value: number) => string;
}

/*
 * The chart is drawn in two layers. The grid and Y axis stay put; the series are drawn once per
 * sample, a little wider than the plot, and slid left by a transform animation until the next
 * sample replaces them. Moving the plot never re-renders or repaints the SVG, so its cost does not
 * depend on the frame rate or on how expensive the active theme makes painting.
 */
const LazyRechartsAreaChart = lazy(async () => {
    const { Area, AreaChart: RechartsAreaChart, CartesianGrid, XAxis, YAxis } = await import('recharts');

    return {
        default: function RechartsAreaChartContent({
            data,
            series,
            live,
            uid,
            width,
            height,
            windowMs,
            yDomain,
            tickFormatter,
        }: RechartsAreaChartContentProps) {
            const slider = useRef<HTMLDivElement>(null);
            const plotWidth = Math.max(0, width - Y_AXIS_WIDTH - MARGIN.right);
            const slideDistance = (plotWidth * SLIDE_MS) / windowMs;

            // The newest sample's arrival time is the moment the visible window ends at `anchor - RIGHT_LAG_MS`.
            const anchor = data.at(-1)?.t;
            const right = (anchor ?? 0) - RIGHT_LAG_MS;
            const xDomain = useMemo<[number, number]>(() => [right - windowMs, right + SLIDE_MS], [right, windowMs]);

            useLayoutEffect(() => {
                if (!slider.current || anchor === undefined) {
                    return;
                }

                const animation = slider.current.animate(
                    [{ transform: 'translateX(0)' }, { transform: `translateX(${-slideDistance}px)` }],
                    { duration: SLIDE_MS, easing: 'linear', fill: 'forwards' }
                );

                animation.currentTime = Math.min(SLIDE_MS, Math.max(0, performance.now() - anchor));

                if (!live) {
                    animation.pause();
                }

                return () => animation.cancel();
            }, [anchor, live, slideDistance]);

            return (
                <>
                    <div className='absolute inset-0'>
                        <RechartsAreaChart width={width} height={height} data={data} margin={MARGIN}>
                            <CartesianGrid stroke={GRID_COLOR} strokeDasharray='0' vertical={false} />
                            <XAxis dataKey='t' type='number' domain={xDomain} allowDataOverflow hide />
                            <YAxis
                                width={Y_AXIS_WIDTH}
                                tickCount={3}
                                domain={yDomain}
                                tickFormatter={tickFormatter}
                                axisLine={false}
                                tickLine={false}
                                tick={{
                                    fill: TICK_COLOR,
                                    fontFamily: SANS_FONT,
                                    fontSize: 'var(--text-xs)',
                                    fontWeight: 'var(--font-weight-normal)',
                                }}
                            />
                            {/* Undrawn copies of the series: the axis only ticks for plotted data, and
                                matching inputs give it the same scale as the sliding layer. */}
                            {series.map(({ dataKey }) => (
                                <Area
                                    key={dataKey}
                                    dataKey={dataKey}
                                    stroke='none'
                                    fill='none'
                                    isAnimationActive={false}
                                    dot={false}
                                    activeDot={false}
                                />
                            ))}
                        </RechartsAreaChart>
                    </div>
                    <div
                        className='absolute top-0 bottom-0 overflow-hidden'
                        style={{ left: Y_AXIS_WIDTH, right: MARGIN.right }}
                    >
                        <div ref={slider} className='will-change-transform'>
                            <RechartsAreaChart
                                width={plotWidth + slideDistance}
                                height={height}
                                data={data}
                                margin={{ ...MARGIN, right: 0 }}
                            >
                                <defs>
                                    {series.map(({ dataKey, color }) => (
                                        <linearGradient
                                            key={dataKey}
                                            id={`${uid}-fill-${dataKey}`}
                                            x1='0'
                                            y1='0'
                                            x2='0'
                                            y2='1'
                                        >
                                            <stop offset='0%' stopColor={color} stopOpacity={0.45} />
                                            <stop offset='100%' stopColor={color} stopOpacity={0} />
                                        </linearGradient>
                                    ))}
                                    {series.map(({ dataKey, color }) => (
                                        <filter
                                            key={dataKey}
                                            id={`${uid}-glow-${dataKey}`}
                                            x='-20%'
                                            y='-20%'
                                            width='140%'
                                            height='140%'
                                        >
                                            <feDropShadow
                                                dx='0'
                                                dy='0'
                                                stdDeviation='2.5'
                                                floodColor={color}
                                                floodOpacity={0.7}
                                            />
                                        </filter>
                                    ))}
                                </defs>
                                <XAxis dataKey='t' type='number' domain={xDomain} allowDataOverflow hide />
                                <YAxis hide tickCount={3} domain={yDomain} />
                                {series.map(({ dataKey, color }) => (
                                    <Area
                                        key={dataKey}
                                        type='monotone'
                                        dataKey={dataKey}
                                        stroke={color}
                                        strokeWidth={2}
                                        fill={`url(#${uid}-fill-${dataKey})`}
                                        filter={`url(#${uid}-glow-${dataKey})`}
                                        isAnimationActive={false}
                                        dot={false}
                                        activeDot={false}
                                        connectNulls={false}
                                    />
                                ))}
                            </RechartsAreaChart>
                        </div>
                    </div>
                </>
            );
        },
    };
});

export default function AreaChart({ data, series, live, windowMs, suggestedMax, tickFormatter, height = 180 }: Props) {
    const uid = useId().replaceAll(':', '');
    const container = useRef<HTMLDivElement>(null);
    const width = useElementWidth(container);

    const yDomain = useMemo<YDomain>(
        () => (suggestedMax === undefined ? [0, 'auto'] : [0, (dataMax: number) => Math.max(suggestedMax, dataMax)]),
        [suggestedMax]
    );

    return (
        <div ref={container} className='relative' style={{ height }}>
            {width > 0 && (
                <Suspense>
                    <LazyRechartsAreaChart
                        data={data}
                        series={series}
                        live={live}
                        uid={uid}
                        width={width}
                        height={height}
                        windowMs={windowMs}
                        yDomain={yDomain}
                        tickFormatter={tickFormatter}
                    />
                </Suspense>
            )}
        </div>
    );
}
