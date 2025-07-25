import React from 'react';
import MessageBox from '@/components/MessageBox';
import { useStoreState } from 'easy-peasy';
import tw from 'twin.macro';

type Props = Readonly<{
    byKey?: string;
    className?: string;
}>;

const FlashMessageRender = ({ byKey, className }: Props) => {
    const flashes = useStoreState((state) =>
        state.flashes.items.filter((flash) => (byKey ? flash.key === byKey : true))
    );

    return flashes.length ? (
        <div className={className}>
            {flashes.map((flash, index) => (
                <React.Fragment key={flash.id || flash.type + flash.message}>
                    {index > 0 && <div css={tw`mt-2`}></div>}
                    <MessageBox type={flash.type} title={flash.title}>
                        {flash.message}
                    </MessageBox>
                </React.Fragment>
            ))}
        </div>
    ) : null;
};

export default FlashMessageRender;
