import tw from 'twin.macro';
import React, { memo } from 'react';
import isEqual from 'react-fast-compare';
import { IconProp } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

interface Props {
    icon?: IconProp;
    title: string | React.ReactNode;
    className?: string;
    children: React.ReactNode;
}

const TitledGreyBox = ({ icon, title, children, className }: Props) => (
    <div css={tw`rounded shadow-md bg-neutral-700`} className={className}>
        <div css={tw`bg-neutral-900 rounded-t p-3 border-b border-black`}>
            {typeof title === 'string' ?
                <p css={tw`text-sm uppercase`}>
                    {icon && <FontAwesomeIcon icon={icon} css={tw`mr-2 text-neutral-300`}/>}{title}
                </p>
                :
                title
            }
        </div>
        <div css={tw`p-3`}>
            {children}
        </div>
    </div>
);

export default memo(TitledGreyBox, isEqual);
