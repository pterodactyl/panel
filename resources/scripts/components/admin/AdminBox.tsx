import type { IconProp } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import type { ReactNode } from 'react';
import tw from 'twin.macro';

import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

interface Props {
    icon?: IconProp;
    isLoading?: boolean;
    title: string | ReactNode;
    className?: string;
    noPadding?: boolean;
    children: ReactNode;
    button?: ReactNode;
}

const AdminBox = ({ icon, title, className, isLoading, children, button, noPadding }: Props) => (
    <div css={tw`relative rounded shadow-md bg-neutral-700`} className={className}>
        <SpinnerOverlay visible={isLoading || false} />
        <div css={tw`flex flex-row bg-neutral-900 rounded-t px-4 xl:px-5 py-3 border-b border-black`}>
            {typeof title === 'string' ? (
                <p css={tw`text-sm uppercase`}>
                    {icon && <FontAwesomeIcon icon={icon} css={tw`mr-2 text-neutral-300`} />}
                    {title}
                </p>
            ) : (
                title
            )}
            {button}
        </div>
        <div css={[!noPadding && tw`px-4 xl:px-5 py-5`]}>{children}</div>
    </div>
);

export default AdminBox;
