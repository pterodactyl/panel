import type React from 'react';
import { replaceable, useSkin } from '@/themes/skins';

export interface ChartCardProps {
    title: string;
    /** Shown at the end of the header, such as the series' colours. */
    legend?: React.ReactNode;
    className?: string;
    /** The chart, usually an `AreaChart`. */
    children?: React.ReactNode;
}

/** The frame of a chart: a titled surface with an optional legend. */
function CoreChartCard({ title, legend, className, children }: ChartCardProps) {
    const ui = useSkin('ChartCard', {});

    return (
        <div {...ui('root', className)}>
            <div {...ui('header')}>
                <h3 {...ui('title')}>{title}</h3>
                {legend && <div {...ui('legend')}>{legend}</div>}
            </div>
            <div {...ui('body')}>{children}</div>
        </div>
    );
}

const ChartCard = replaceable('ChartCard', CoreChartCard);

export default ChartCard;
