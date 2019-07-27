import React from 'react';
import classNames from 'classnames';

type Props = { isLoading?: boolean } & React.DetailedHTMLProps<React.ButtonHTMLAttributes<HTMLButtonElement>, HTMLButtonElement>;

export default ({ isLoading, children, className, ...props }: Props) => (
    <button
        {...props}
        className={classNames('btn btn-sm relative', className)}
    >
        {isLoading &&
        <div className={'w-full flex absolute justify-center'} style={{ marginLeft: '-0.75rem' }}>
            <div className={'spinner-circle spinner-white spinner-sm'}/>
        </div>
        }
        <span className={isLoading ? 'text-transparent' : undefined}>
            {children}
        </span>
    </button>
);
