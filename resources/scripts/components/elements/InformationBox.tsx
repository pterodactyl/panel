import React from 'react';
import { IconProp } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

interface Props {
    icon: IconProp;
    iconCss?: string;
    children: React.ReactChild[] | React.ReactChild;
}

export default ({ children, icon, iconCss }: Props) => (
    <div className={'w-1/4 pointer-events-none bg-gray-700 p-2 my-2 rounded justify-between'}>
        <p className={'text-neutral-500 font-normal truncate'}>
            <FontAwesomeIcon icon={icon} className={'text-neutral-400 mr-2 ' + iconCss} />
            {children}
        </p>
    </div>
);
