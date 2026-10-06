import { lazy, Suspense, useId, useMemo, useRef } from 'react';
import { type ChartPoint, useAnimationClock, useElementVisible } from '@/components/server/console/chart';
import type { ChartSeries } from '@/components/server/console/types';

const RIGHT_LAG_MS = 1100;
const HEADROOM = 1.15;

interface Props {
    data: ChartPoint[];
    series: ChartSeries[];
    live: boolean;
    windowMs: number;
    floor?: number;
    limit?: number;
    binary?: boolean;
    tickFormatter: (value: number) => string;
    height?: number;
}

const NICE_STEPS = [1, 1.2, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10];
const BINARY_STEPS = [1, 2, 3, 4, 5, 6, 8, 10];

export function niceCeil(value: number, binary = false): number {
    if (!Number.isFinite(value) || value <= 0) return 1;

    const unit = binary && value >= 1024 ? Math.pow(1024, Math.floor(Math.log(value) / Math.log(1024))) : 1;
    const scaled = value / unit;
    const magnitude = Math.pow(10, Math.floor(Math.log10(scaled)));
    const steps = binary ? BINARY_STEPS : NICE_STEPS;
    const step = steps.find((s) => s * magnitude >= scaled) ?? 10;

    if (binary && step * magnitude >= 1000) {
        return unit * 1024;
    }

    return Number((step * magnitude * unit).toPrecision(12));
}

export function axisMax(data: ChartPoint[], series: ChartSeries[], floor = 0, limit = 0, binary = false): number {
    let dataMax = 0;
    for (const point of data) {
        for (const { dataKey } of series) {
            const value = point[dataKey];
            if (value !== null && value !== undefined && value > dataMax) dataMax = value;
        }
    }

    const fitted = niceCeil(Math.max(dataMax * HEADROOM, floor), binary);

    if (limit > 0 && dataMax <= limit && fitted > limit) {
        return limit;
    }

    return fitted;
}

interface TickProps {
    x?: number | string;
    y?: number | string;
    payload?: { value: number };
}

interface ContentProps {
    data: ChartPoint[];
    series: ChartSeries[];
    uid: string;
    height: number;
    xDomain: [number, number];
    yMax: number;
    limit?: number;
    tickFormatter: (value: number) => string;
}

const LazyRechartsAreaChart = lazy(async () => {
    const {
        Area,
        AreaChart: RechartsAreaChart,
        CartesianGrid,
        ReferenceLine,
        ResponsiveContainer,
        Tooltip,
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
            yMax,
            limit,
            tickFormatter,
        }: ContentProps) {
            const showLimit = limit !== undefined && limit > 0 && limit < yMax;

            return (
                <ResponsiveContainer width={'100%'} height={height}>
                    <RechartsAreaChart
                        data={data}
                        margin={{ top: 10, right: 0, bottom: 8, left: 16 }}
                        accessibilityLayer={false}
                    >
                        <defs>
                            {series.map(({ dataKey, color }) => (
                                <linearGradient
                                    key={dataKey}
                                    id={`${uid}-fill-${dataKey}`}
                                    x1={'0'}
                                    y1={'0'}
                                    x2={'0'}
                                    y2={'1'}
                                >
                                    <stop offset={'0%'} stopColor={color} stopOpacity={0.28} />
                                    <stop offset={'100%'} stopColor={color} stopOpacity={0.02} />
                                </linearGradient>
                            ))}
                        </defs>
                        <CartesianGrid
                            stroke={'var(--muted-foreground)'}
                            strokeOpacity={0.18}
                            vertical={false}
                            syncWithTicks
                        />
                        <XAxis dataKey={'t'} type={'number'} domain={xDomain} allowDataOverflow hide />
                        <YAxis
                            orientation={'right'}
                            width={72}
                            domain={[0, yMax]}
                            ticks={[0, yMax / 2, yMax]}
                            interval={0}
                            allowDataOverflow
                            axisLine={false}
                            tickLine={false}
                            tickMargin={10}
                            tick={({ x, y, payload }: TickProps) => (
                                <text
                                    x={Number(x)}
                                    y={Number(y)}
                                    dy={4}
                                    textAnchor={'start'}
                                    fill={'var(--muted-foreground)'}
                                    fontFamily={'var(--font-sans)'}
                                    fontSize={11}
                                >
                                    {tickFormatter(payload?.value ?? 0)}
                                </text>
                            )}
                        />
                        {showLimit && (
                            <ReferenceLine
                                y={limit}
                                stroke={'var(--destructive)'}
                                strokeOpacity={0.7}
                                strokeDasharray={'4 4'}
                                ifOverflow={'hidden'}
                            />
                        )}
                        <Tooltip
                            isAnimationActive={false}
                            cursor={{ stroke: 'var(--muted-foreground)', strokeOpacity: 0.6, strokeWidth: 1 }}
                            content={({ active, payload, label }) => {
                                if (!active || !payload || payload.length === 0) return null;
                                const ago = Math.max(0, Math.round((xDomain[1] - Number(label)) / 1000));

                                return (
                                    <div
                                        className={
                                            'rounded-sm border border-border bg-background/95 px-2.5 py-1.5 text-xs shadow-lg tabular-nums'
                                        }
                                    >
                                        {payload.map((row) => (
                                            <div key={String(row.dataKey)} className={'flex items-center gap-2'}>
                                                <span
                                                    className={'h-2 w-2 rounded-full'}
                                                    style={{ background: row.color }}
                                                />
                                                <span className={'font-semibold text-foreground'}>
                                                    {tickFormatter(Number(row.value ?? 0))}
                                                </span>
                                            </div>
                                        ))}
                                        <div className={'mt-0.5 text-muted-foreground'}>
                                            {ago === 0 ? 'now' : `${ago}s ago`}
                                        </div>
                                    </div>
                                );
                            }}
                        />
                        {series.map(({ dataKey, color }) => (
                            <Area
                                key={dataKey}
                                type={'monotoneX'}
                                dataKey={dataKey}
                                stroke={color}
                                strokeWidth={2}
                                strokeLinejoin={'round'}
                                fill={`url(#${uid}-fill-${dataKey})`}
                                isAnimationActive={false}
                                dot={false}
                                activeDot={{ r: 3.5, fill: color, stroke: 'var(--popover)', strokeWidth: 2 }}
                                connectNulls={false}
                            />
                        ))}
                    </RechartsAreaChart>
                </ResponsiveContainer>
            );
        },
    };
});

export default function AreaChart({
    data,
    series,
    live,
    windowMs,
    floor,
    limit,
    binary = false,
    tickFormatter,
    height = 132,
}: Props) {
    const uid = useId().replace(/:/g, '');
    const container = useRef<HTMLDivElement>(null);
    const visible = useElementVisible(container);
    const now = useAnimationClock(live && visible);

    const right = now - RIGHT_LAG_MS;
    const xDomain = useMemo<[number, number]>(() => [right - windowMs, right], [right, windowMs]);
    const yMax = useMemo(() => axisMax(data, series, floor, limit, binary), [data, series, floor, limit, binary]);

    return (
        <div ref={container} className={'[&_.recharts-wrapper_*:focus]:outline-none'}>
            <Suspense fallback={<div style={{ height }} />}>
                <LazyRechartsAreaChart
                    data={data}
                    series={series}
                    uid={uid}
                    height={height}
                    xDomain={xDomain}
                    yMax={yMax}
                    limit={limit}
                    tickFormatter={tickFormatter}
                />
            </Suspense>
        </div>
    );
}
