import * as React from 'react';
import MessageBox from '@/components/MessageBox';

export default ({ message }: { message: string | undefined | null }) => (
    !message ?
        null
        :
        <div className={'mb-4'}>
            <MessageBox type={'error'} title={'Error'}>
                {message}
            </MessageBox>
        </div>
);
