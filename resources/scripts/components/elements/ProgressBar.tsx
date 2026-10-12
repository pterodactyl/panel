import { useEffect } from 'react';
import { setHttpProgress, useHttpProgress } from '@/state/httpProgress';
import { randomInt } from '@/helpers';
import { replaceable, useSkin, type UiTone } from '@/themes/skins';

/** `meter` shows a value in a track; `page` is the loading bar along the top of the page. */
export type ProgressBarVariant = 'meter' | 'page';

export interface ProgressBarProps {
    /** Percent complete from 0 to 100; `null` shows the track alone. */
    value: number | null;
    variant?: ProgressBarVariant;
    /** Defaults to `accent` for the page bar and `neutral` otherwise. */
    tone?: UiTone;
    /** Names a meter for assistive technology. */
    label?: string;
    className?: string;
}

function CoreProgressBar({ value, variant = 'meter', tone, label, className }: ProgressBarProps) {
    const meter = variant === 'meter';
    const ui = useSkin('ProgressBar', { variant, tone: tone ?? (meter ? 'neutral' : 'accent') });

    return (
        <div
            {...ui('root', className)}
            role={meter ? 'progressbar' : undefined}
            aria-label={meter ? label : undefined}
            aria-valuemin={meter ? 0 : undefined}
            aria-valuemax={meter ? 100 : undefined}
            aria-valuenow={meter ? (value ?? undefined) : undefined}
        >
            {value !== null && <div {...ui('indicator')} style={{ width: `${value}%` }} />}
        </div>
    );
}

const ProgressBar = replaceable('ProgressBar', CoreProgressBar);

export default ProgressBar;

/** The page loading bar, driven by the panel's HTTP requests. */
export function HttpProgressBar() {
    const { progress, continuous } = useHttpProgress();
    const visible = (progress || 0) > 0;

    useEffect(() => {
        if (progress !== 100) {
            return;
        }

        const timeout = setTimeout(() => setHttpProgress(undefined), 500);

        return () => clearTimeout(timeout);
    }, [progress]);

    useEffect(() => {
        if (!continuous) {
            return;
        }

        if (!progress || progress === 0) {
            setHttpProgress(randomInt(20, 30));

            return;
        }

        if (progress >= 90) {
            setHttpProgress(90);

            return;
        }

        const timeout = setTimeout(() => setHttpProgress(progress + randomInt(1, 5)), 500);

        return () => clearTimeout(timeout);
    }, [continuous, progress]);

    return <ProgressBar variant='page' value={visible ? (progress ?? 100) : null} />;
}
