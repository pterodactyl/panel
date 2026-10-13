import type React from 'react';
import type { LucideIcon } from 'lucide-react';
import ProgressBar from '@/components/elements/ProgressBar';
import { replaceable, useSkin, type UiTone } from '@/themes/skins';

/** `tile`: a block with an icon tile, as on the console; `inline`: an icon beside the value, as in a list row. */
export type StatVariant = 'tile' | 'inline';

export interface StatProps extends Omit<React.HTMLAttributes<HTMLDivElement>, 'children'> {
    /** What the value measures, such as "Memory". */
    label: string;
    /** The value as text, such as "1.4 GiB". */
    value: React.ReactNode;
    /** The limit as text after the value, such as "/ 2 GiB"; leave it out when there is none. */
    limit?: React.ReactNode;
    icon?: LucideIcon;
    /** The percent of the limit in use, drawn as a meter; null draws the empty track. */
    meter?: number | null;
    /** The colour of the value's state, such as an alarm's (`danger` above 90% of a limit). */
    tone?: UiTone;
    variant?: StatVariant;
    /** The value says why there is no reading, such as "Offline". */
    unavailable?: boolean;
}

/** A labelled value with an optional icon, limit and meter, such as a server's memory use. */
function CoreStat({
    label,
    value,
    limit,
    icon: Icon,
    meter,
    tone = 'neutral',
    variant = 'tile',
    unavailable = false,
    className,
    ...props
}: StatProps) {
    const ui = useSkin('Stat', { variant, tone, unavailable });

    return (
        <div {...ui('root', className)} {...props}>
            <div {...ui('indicator')} />
            {Icon && (
                <div {...ui('icon')}>
                    <Icon aria-hidden {...ui('glyph')} />
                </div>
            )}
            <div {...ui('body')}>
                <p {...ui('label')}>{label}</p>
                <p {...ui('value')}>{value}</p>
                {limit !== undefined && <span {...ui('limit')}>{limit}</span>}
                {meter !== undefined && <ProgressBar value={meter} tone={tone} label={label} />}
            </div>
        </div>
    );
}

const Stat = replaceable('Stat', CoreStat);

export default Stat;
