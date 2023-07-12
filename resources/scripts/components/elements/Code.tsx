import * as React from 'react';
import classNames from 'classnames';

interface CodeProps {
    dark?: boolean | undefined;
    className?: string;
    children: React.ReactChild | React.ReactFragment | React.ReactPortal;
}

export default ({ dark, className, children }: CodeProps) => (
    <code
        className={classNames('inline-block rounded px-2 py-1 font-mono text-sm', className, {
            'bg-neutral-700': !dark,
            'bg-neutral-900 text-slate-100': dark,
        })}
    >
        {children}
    </code>
);
