import type React from 'react';
import type { LucideIcon } from 'lucide-react';
import Card from '@/components/elements/Card';

interface Props {
    icon?: LucideIcon;
    title: string | React.ReactNode;
    actions?: React.ReactNode;
    className?: string;
    contentClassName?: string;
    children: React.ReactNode;
}

/** A `Card` with a header. */
const TitledGreyBox = ({ icon, title, actions, children, className, contentClassName }: Props) => (
    <Card title={title} icon={icon} actions={actions} className={className} bodyClassName={contentClassName}>
        {children}
    </Card>
);

export default TitledGreyBox;
