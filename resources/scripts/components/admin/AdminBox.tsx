import React, { memo } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { IconProp } from '@fortawesome/fontawesome-svg-core';
import tw from 'twin.macro';
import isEqual from 'react-fast-compare';

interface Props {
    icon?: IconProp;
    title: string | React.ReactNode;
    className?: string;
    padding?: boolean;
    children: React.ReactNode;
    button?: React.ReactNode;
}

const AdminBox = ({ icon, title, className, padding, children, button }: Props) => {
    if (padding === undefined) {
        padding = true;
    }

    return (
        <div css={tw`rounded shadow-md bg-neutral-700`} className={className}>
            <div css={tw`flex flex-row bg-neutral-900 rounded-t px-4 py-3 border-b border-black`}>
                {typeof title === 'string' ?
                    <p css={tw`text-sm uppercase`}>
                        {icon && <FontAwesomeIcon icon={icon} css={tw`mr-2 text-neutral-300`}/>}{title}
                    </p>
                    :
                    title
                }
                {button}
            </div>
            <div css={padding ? tw`px-4 py-3` : undefined}>
                {children}
            </div>
        </div>
    );
};

export default memo(AdminBox, isEqual);
