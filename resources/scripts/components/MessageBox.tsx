import * as React from 'react';

export type FlashMessageType = 'success' | 'info' | 'warning' | 'error';

interface Props {
    title?: string;
    children: string;
    type?: FlashMessageType;
}

export default ({ title, children, type }: Props) => (
    <div className={`lg:inline-flex alert ${type}`} role={'alert'}>
        {title && <span className={'title'}>{title}</span>}
        <span className={'message'}>
            {children}
        </span>
    </div>
);
