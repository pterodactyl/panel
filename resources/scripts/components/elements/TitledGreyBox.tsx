import type React from 'react';
import type { LucideIcon } from 'lucide-react';
import Icon from '@/components/elements/Icon';
import { cn } from '@/lib/cn';
import { cardTitleClass } from '@/components/ui/typography';
import { isString } from '@/lib/objects';

interface Props {
    icon?: LucideIcon;
    title: string | React.ReactNode;
    className?: string;
    contentClassName?: string;
    children: React.ReactNode;
}

const TitledGreyBox = ({ icon, title, children, className, contentClassName }: Props) => (
    <div className={cn('rounded-sm shadow-md bg-card', className)}>
        <div className='bg-muted rounded-t-sm p-3 border-b border-border'>
            {isString(title) ? (
                <h2 className={cardTitleClass}>
                    {icon && <Icon icon={icon} className='mr-2 text-muted-foreground' />}
                    {title}
                </h2>
            ) : (
                title
            )}
        </div>
        <div className={cn('p-3', contentClassName)}>{children}</div>
    </div>
);

export default TitledGreyBox;
