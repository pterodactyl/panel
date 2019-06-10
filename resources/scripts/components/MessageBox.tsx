import * as React from 'react';

interface Props {
    title?: string;
    message: string;
    type?: 'success' | 'info' | 'warning' | 'error';
}

export default ({ title, message, type }: Props) => (
    <div className={`lg:inline-flex alert ${type}`} role={'alert'}>
        {title && <span className={'title'}>{title}</span>}
        <span className={'message'}>{message}</span>
    </div>
);
