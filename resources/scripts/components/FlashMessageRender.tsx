import React from 'react';
import tw from 'twin.macro';
import { useStoreState } from 'easy-peasy';
import Alert from '@/components/elements/alert/Alert';

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
                    <Alert type={flash.type}>{flash.message}</Alert>
                </React.Fragment>
            ))}
        </div>
    ) : null;
};

export default FlashMessageRender;
