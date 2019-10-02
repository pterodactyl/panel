import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { IconProp } from '@fortawesome/fontawesome-svg-core';

interface Props {
    icon?: IconProp;
    title: string;
    className?: string;
    children: React.ReactNode;
}

export default ({ icon, title, children, className }: Props) => (
    <div className={`rounded shadow-md bg-neutral-700 ${className}`}>
        <div className={'bg-neutral-900 rounded-t p-3 border-b border-black'}>
            <p className={'text-sm uppercase'}>
                {icon && <FontAwesomeIcon icon={icon} className={'mr-2 text-neutral-300'}/>}{title}
            </p>
        </div>
        <div className={'p-3'}>
            {children}
        </div>
    </div>
);
