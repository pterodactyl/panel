import { useEffect } from 'react';
import { setHttpProgress, useHttpProgress } from '@/state/httpProgress';
import { randomInt } from '@/helpers';

export default function ProgressBar() {
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

    return (
        <div className='fixed h-0.5 w-full'>
            {visible && (
                <div
                    className='h-full bg-accent shadow-progress transition-[width] duration-300 ease-in-out'
                    style={{ width: progress === undefined ? '100%' : `${progress}%` }}
                />
            )}
        </div>
    );
}
