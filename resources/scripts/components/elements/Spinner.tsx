import React from 'react';
import classNames from 'classnames';

export default ({ large }: { large?: boolean }) => (
    <div className={classNames('spinner-circle spinner-white', { 'spinner-lg': large })}/>
);
