import React from 'react';
import { cn } from '@/lib/cn';
import { interactiveSurfaceClass } from '@/components/ui/styles';

type Props<E extends React.ElementType = 'div'> = {
    as?: E;
    $hoverable?: boolean;
} & Omit<React.ComponentPropsWithoutRef<E>, 'as' | '$hoverable'>;

const GreyRowBox = <E extends React.ElementType = 'div'>({ as, $hoverable, className, ...props }: Props<E>) => {
    const Component = (as || 'div') as React.ElementType;

    return (
        <Component
            className={cn(
                'flex rounded-sm no-underline text-foreground items-center bg-card p-4 border border-transparent overflow-hidden',
                '[&_.icon]:flex [&_.icon]:w-16 [&_.icon]:items-center [&_.icon]:justify-center [&_.icon]:rounded-full [&_.icon]:bg-popover [&_.icon]:p-3',
                $hoverable !== false && interactiveSurfaceClass,
                className
            )}
            {...props}
        />
    );
};

export default GreyRowBox;
