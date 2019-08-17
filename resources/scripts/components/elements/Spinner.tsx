import React from 'react';
import classNames from 'classnames';

export type SpinnerSize = 'large' | 'normal' | 'tiny';

export default ({ size, centered }: { size?: SpinnerSize; centered?: boolean }) => (
    centered ?
        <div className={classNames('flex justify-center', { 'm-20': size === 'large', 'm-6': size !== 'large' })}>
            <div className={classNames('spinner-circle spinner-white', {
                'spinner-lg': size === 'large',
                'spinner-sm': size === 'tiny',
            })}/>
        </div>
        :
        <div className={classNames('spinner-circle spinner-white', {
            'spinner-lg': size === 'large',
            'spinner-sm': size === 'tiny',
        })}/>
);
