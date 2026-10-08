import { use, useEffect } from 'react';
import { Check, TriangleAlert, Info, ShieldAlert } from 'lucide-react';
import { cn } from '@/lib/cn';
import { DialogContext } from './context';
import type { DialogIconProps } from './types';

const icons = {
    danger: ShieldAlert,
    warning: TriangleAlert,
    success: Check,
    info: Info,
};

const iconClass = 'flex items-center justify-center w-10 h-10 rounded-full mr-4';
const iconTypeClass = {
    danger: 'bg-destructive text-destructive-foreground',
    warning: 'bg-warning text-warning-foreground',
    success: 'bg-success text-success-foreground',
    info: 'bg-primary text-primary-foreground',
};

const DialogIcon = ({ type, position, className }: DialogIconProps) => {
    const { setIcon, setIconPosition } = use(DialogContext);

    useEffect(() => {
        const Icon = icons[type];

        setIcon(
            <div className={cn(iconClass, iconTypeClass[type], className)}>
                <Icon className='w-6 h-6' />
            </div>
        );

        return () => setIcon(undefined);
    }, [className, setIcon, type]);

    useEffect(() => {
        setIconPosition(position);

        return () => setIconPosition('title');
    }, [position, setIconPosition]);

    return null;
};

export default DialogIcon;
