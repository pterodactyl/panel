import React from 'react';
import classNames from 'classnames';

export default ({ large, centered }: { large?: boolean; centered?: boolean }) => (
    centered ?
        <div className={classNames('flex justify-center', { 'm-20': large, 'm-6': !large })}>
            <div className={classNames('spinner-circle spinner-white', { 'spinner-lg': large })}/>
        </div>
        :
        <div className={classNames('spinner-circle spinner-white', { 'spinner-lg': large })}/>
);
