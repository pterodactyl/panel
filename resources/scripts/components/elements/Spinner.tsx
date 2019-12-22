import React from 'react';
import classNames from 'classnames';

export type SpinnerSize = 'large' | 'normal' | 'tiny';

interface Props {
    size?: SpinnerSize;
    centered?: boolean;
    className?: string;
}

export default ({ size, centered, className }: Props) => (
    centered ?
        <div className={classNames(`flex justify-center ${className}`, { 'm-20': size === 'large', 'm-6': size !== 'large' })}>
            <div
                className={classNames('spinner-circle spinner-white', {
                    'spinner-lg': size === 'large',
                    'spinner-sm': size === 'tiny',
                })}
            />
        </div>
        :
        <div
            className={classNames(`spinner-circle spinner-white ${className}`, {
                'spinner-lg': size === 'large',
                'spinner-sm': size === 'tiny',
            })}
        />
);
