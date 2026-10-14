import type React from 'react';
import type { LucideIcon } from 'lucide-react';
import Card from '@/components/elements/Card';

interface Props {
    icon?: LucideIcon;
    title: string | React.ReactNode;
    className?: string;
    contentClassName?: string;
    children: React.ReactNode;
}

/** A `Card` with a header. */
const TitledGreyBox = ({ icon, title, children, className, contentClassName }: Props) => (
    <Card title={title} icon={icon} className={className} bodyClassName={contentClassName}>
        {children}
    </Card>
);

export default TitledGreyBox;
