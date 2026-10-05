import type { LucideIcon, LucideProps } from 'lucide-react';
import { cn } from '@/lib/cn';

interface Props extends Omit<LucideProps, 'ref'> {
    icon: LucideIcon;
    className?: string;
}

/** Renders a lucide icon inline at the surrounding text size (1em). */
const Icon = ({ icon: IconComponent, className, size = '1em', ...rest }: Props) => (
    <IconComponent className={cn('inline-block', className)} size={size} {...rest} />
);

export default Icon;
