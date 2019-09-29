import React, { useEffect, useState } from 'react';
import useRouter from 'use-react-router';
import { ServerContext } from '@/state/server';
import getFileContents from '@/api/server/files/getFileContents';

export default () => {
    const { location: { hash } } = useRouter();
    const [content, setContent] = useState('');
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    useEffect(() => {
        getFileContents(uuid, hash.replace(/^#/, ''))
            .then(setContent)
            .catch(error => console.error(error));
    }, []);

    return (
        <div className={'my-10'}>
            <textarea
                value={content}
                className={'rounded bg-black h-32 w-full text-neutral-100 text-sm font-mono'}
            />
        </div>
    );
};
