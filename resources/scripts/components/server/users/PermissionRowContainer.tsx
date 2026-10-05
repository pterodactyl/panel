import React from 'react';
import { cn } from '@/lib/cn';

type Props = React.ComponentProps<'label'> & {
    htmlFor: string;
};

const PermissionRowContainer = ({ className, htmlFor, ...props }: Props) => (
    <label
        htmlFor={htmlFor}
        className={cn(
            'flex items-center border border-transparent rounded-sm md:p-2 transition-colors duration-75 normal-case',
            '[&:not(.disabled)]:cursor-pointer [&:not(.disabled):hover]:border-border [&:not(.disabled):hover]:bg-background',
            'not-first-of-type:mt-4 sm:not-first-of-type:mt-2',
            '[&.disabled]:opacity-50',
            className
        )}
        {...props}
    />
);

export default PermissionRowContainer;
