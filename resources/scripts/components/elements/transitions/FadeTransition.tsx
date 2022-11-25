import { Transition } from '@headlessui/react';
import type { ElementType, ReactNode } from 'react';

type Duration = `duration-${number}`;

interface Props {
    as?: ElementType;
    duration?: Duration | [Duration, Duration];
    appear?: boolean;
    unmount?: boolean;
    show: boolean;
    children: ReactNode;
}

function FadeTransition({ children, duration, ...props }: Props) {
    const [enterDuration, exitDuration] = Array.isArray(duration)
        ? duration
        : !duration
        ? ['duration-200', 'duration-100']
        : [duration, duration];

    return (
        <Transition
            {...props}
            enter={`ease-out ${enterDuration}`}
            enterFrom={'opacity-0'}
            enterTo={'opacity-100'}
            leave={`ease-in ${exitDuration}`}
            leaveFrom={'opacity-100'}
            leaveTo={'opacity-0'}
        >
            {children}
        </Transition>
    );
}

export default FadeTransition;
