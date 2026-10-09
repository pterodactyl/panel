import React from 'react';
import { Link } from '@tanstack/react-router';
import { cn } from '@/lib/cn';

interface Props extends Omit<React.ComponentProps<'a'>, 'href'> {
    serverId: string;
}

export default function SearchServerResult({ serverId, className, ...props }: Props) {
    return (
        <Link
            to='/server/$id'
            params={{ id: serverId }}
            className={cn(
                'flex items-center bg-muted p-4 rounded-sm border-l-4 border-border no-underline transition-[background-color,border-color,color] duration-150',
                'hover:shadow-sm hover:border-accent',
                'not-last-of-type:mb-2',
                className
            )}
            {...props}
        />
    );
}
