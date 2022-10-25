import * as React from 'react';
import { Transition } from '@headlessui/react';

type Duration = `duration-${number}`;

interface Props {
    as?: React.ElementType;
    duration?: Duration | [Duration, Duration];
    show: boolean;
    children: React.ReactNode;
}

export default ({ children, duration, ...props }: Props) => {
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
};
