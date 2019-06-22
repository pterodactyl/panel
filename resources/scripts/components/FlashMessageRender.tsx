import React from 'react';
import MessageBox from '@/components/MessageBox';
import { State, useStoreState } from 'easy-peasy';
import { ApplicationState } from '@/state/types';

type Props = Readonly<{
    spacerClass?: string;
    withBottomSpace?: boolean;
}>;

export default ({ withBottomSpace, spacerClass }: Props) => {
    const flashes = useStoreState((state: State<ApplicationState>) => state.flashes.items);

    if (flashes.length === 0) {
        return null;
    }

    // noinspection PointlessBooleanExpressionJS
    return (
        <div className={withBottomSpace === false ? undefined : 'mb-2'}>
            {
                flashes.map((flash, index) => (
                    <React.Fragment key={flash.id || flash.type + flash.message}>
                        {index > 0 && <div className={spacerClass || 'mt-2'}></div>}
                        <MessageBox type={flash.type} title={flash.title}>
                            {flash.message}
                        </MessageBox>
                    </React.Fragment>
                ))
            }
        </div>
    );
};
