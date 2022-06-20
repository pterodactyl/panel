import React from 'react';
import classNames from 'classnames';

interface CodeProps {
    dark?: boolean | undefined;
    children: React.ReactChild | React.ReactFragment | React.ReactPortal;
}

export default ({ dark, children }: CodeProps) => (
    <code
        className={classNames('font-mono text-sm px-2 py-1 rounded', {
            'bg-neutral-700': !dark,
            'bg-neutral-900 text-gray-100': dark,
        })}
    >
        {children}
    </code>
);
