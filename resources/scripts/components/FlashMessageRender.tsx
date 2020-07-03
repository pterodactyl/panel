import React from 'react';
import MessageBox from '@/components/MessageBox';
import { State, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';

type Props = Readonly<{
    byKey?: string;
    spacerClass?: string;
}>;

export default ({ spacerClass, byKey }: Props) => {
    const flashes = useStoreState((state: State<ApplicationStore>) => state.flashes.items);

    let filtered = flashes;
    if (byKey) {
        filtered = flashes.filter(flash => flash.key === byKey);
    }

    if (filtered.length === 0) {
        return null;
    }

    return (
        <div>
            {
                filtered.map((flash, index) => (
                    <React.Fragment key={flash.id || flash.type + flash.message}>
                        {index > 0 && <div css={tw`${spacerClass || 'mt-2'}`}></div>}
                        <MessageBox type={flash.type} title={flash.title}>
                            {flash.message}
                        </MessageBox>
                    </React.Fragment>
                ))
            }
        </div>
    );
};
