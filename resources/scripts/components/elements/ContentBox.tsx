import React from 'react';
import Card from '@/components/elements/Card';
import { cn } from '@/lib/cn';
import { sectionTitleClass } from '@/components/ui/typography';

type Props = Readonly<
    React.DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement> & {
        title?: string;
        borderColor?: string;
        showLoadingOverlay?: boolean;
    }
>;

/** A section title above a plain `Card`. */
const ContentBox = ({ title, borderColor, showLoadingOverlay, children, ...props }: Props) => (
    <div {...props}>
        {title && <h2 className={cn(sectionTitleClass, 'mb-4 px-4')}>{title}</h2>}
        <Card loading={showLoadingOverlay} className={borderColor ? 'border-t-4' : undefined}>
            {children}
        </Card>
    </div>
);

export default ContentBox;
