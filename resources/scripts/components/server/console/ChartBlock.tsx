import React from 'react';

interface ChartBlockProps {
    title: string;
    legend?: React.ReactNode;
    children: React.ReactNode;
}

export default function ChartBlock({ title, legend, children }: ChartBlockProps) {
    return (
        <div className={'group relative rounded-sm bg-popover pt-2 shadow-lg border-b-4 border-border'}>
            <div className={'flex items-center justify-between px-4 py-2'}>
                <h3 className={'font-header font-medium transition-colors duration-100 group-hover:text-foreground'}>
                    {title}
                </h3>
                {legend && <p className={'text-sm flex items-center'}>{legend}</p>}
            </div>
            <div className={'z-10 ml-2'}>{children}</div>
        </div>
    );
}
