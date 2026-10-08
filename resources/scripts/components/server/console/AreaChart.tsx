import { lazy, Suspense, useId, useMemo, useRef } from 'react';
import { type ChartPoint, useAnimationClock, useElementVisible } from '@/components/server/console/chart';
import type { ChartSeries } from '@/components/server/console/types';

const GRID_COLOR = 'var(--border)';
const TICK_COLOR = 'var(--muted-foreground)';
const SANS_FONT = 'var(--font-sans)';

// How far the visible right edge trails real time; just over the ~1s stats cadence.
const RIGHT_LAG_MS = 1100;

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
    uid: string;
    height: number;
    xDomain: [number, number];
    yDomain: YDomain;
    tickFormatter?: (value: number) => string;
}

const LazyRechartsAreaChart = lazy(async () => {
    const {
        Area,
        AreaChart: RechartsAreaChart,
        CartesianGrid,
        ResponsiveContainer,
        XAxis,
        YAxis,
    } = await import('recharts');

    return {
        default: function RechartsAreaChartContent({
            data,
            series,
            uid,
            height,
            xDomain,
            yDomain,
            tickFormatter,
        }: RechartsAreaChartContentProps) {
            return (
                <ResponsiveContainer width='100%' height={height}>
                    <RechartsAreaChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
                        <defs>
                            {series.map(({ dataKey, color }) => (
                                <linearGradient key={dataKey} id={`${uid}-fill-${dataKey}`} x1='0' y1='0' x2='0' y2='1'>
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
                        <CartesianGrid stroke={GRID_COLOR} strokeDasharray='0' vertical={false} />
                        <XAxis dataKey='t' type='number' domain={xDomain} allowDataOverflow hide />
                        <YAxis
                            width={64}
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
                </ResponsiveContainer>
            );
        },
    };
});

export default function AreaChart({ data, series, live, windowMs, suggestedMax, tickFormatter, height = 180 }: Props) {
    const uid = useId().replaceAll(':', '');
    const container = useRef<HTMLDivElement>(null);
    const visible = useElementVisible(container);
    const now = useAnimationClock(live && visible);

    const right = now - RIGHT_LAG_MS;
    const xDomain = useMemo<[number, number]>(() => [right - windowMs, right], [right, windowMs]);
    const yDomain = useMemo<YDomain>(
        () => (suggestedMax === undefined ? [0, 'auto'] : [0, (dataMax: number) => Math.max(suggestedMax, dataMax)]),
        [suggestedMax]
    );

    return (
        <div ref={container}>
            <Suspense fallback={<div style={{ height }} />}>
                <LazyRechartsAreaChart
                    data={data}
                    series={series}
                    uid={uid}
                    height={height}
                    xDomain={xDomain}
                    yDomain={yDomain}
                    tickFormatter={tickFormatter}
                />
            </Suspense>
        </div>
    );
}
