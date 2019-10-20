import React, { lazy, useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import getFileContents from '@/api/server/files/getFileContents';
import useRouter from 'use-react-router';

const LazyAceEditor = lazy(() => import(/* webpackChunkName: "editor" */'@/components/elements/AceEditor'));

export default () => {
    const { location: { hash } } = useRouter();
    const [ content, setContent ] = useState('');
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    let ref: null| (() => Promise<string>) = null;

    useEffect(() => {
        getFileContents(uuid, hash.replace(/^#/, ''))
            .then(setContent)
            .catch(error => console.error(error));
    }, [ uuid, hash ]);

    return (
        <div className={'my-10 mb-4'}>
            <LazyAceEditor
                initialModePath={hash.replace(/^#/, '') || 'plain_text'}
                initialContent={content}
                fetchContent={value => {
                    ref = value;
                }}
                onContentSaved={() => null}
            />
            <div className={'flex justify-end mt-4'}>
                <button className={'btn btn-primary btn-sm'}>
                    Save Content
                </button>
            </div>
        </div>
    );
};
