import { useStoreState } from 'easy-peasy';
import { Fragment } from 'react';

import MessageBox from '@/components/MessageBox';

type Props = Readonly<{
    byKey?: string;
    className?: string;
}>;

function FlashMessageRender({ byKey, className }: Props) {
    const flashes = useStoreState(state => state.flashes.items.filter(flash => (byKey ? flash.key === byKey : true)));

    return flashes.length ? (
        <div className={className}>
            {flashes.map((flash, index) => (
                <Fragment key={flash.id || flash.type + flash.message}>
                    {index > 0 && <div className="mt-2" />}
                    <MessageBox type={flash.type} title={flash.title}>
                        {flash.message}
                    </MessageBox>
                </Fragment>
            ))}
        </div>
    ) : null;
}

export default FlashMessageRender;
