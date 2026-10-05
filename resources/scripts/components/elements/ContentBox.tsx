import React from 'react';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { cn } from '@/lib/cn';
import { sectionTitleClass } from '@/components/ui/typography';

type Props = Readonly<
    React.DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement> & {
        title?: string;
        borderColor?: string;
        showLoadingOverlay?: boolean;
    }
>;

const ContentBox = ({ title, borderColor, showLoadingOverlay, children, ...props }: Props) => (
    <div {...props}>
        {title && <h2 className={cn(sectionTitleClass, 'mb-4 px-4')}>{title}</h2>}
        <div className={cn('bg-card p-4 rounded-sm shadow-lg relative', !!borderColor && 'border-t-4')}>
            <SpinnerOverlay visible={showLoadingOverlay || false} />
            {children}
        </div>
    </div>
);

export default ContentBox;
