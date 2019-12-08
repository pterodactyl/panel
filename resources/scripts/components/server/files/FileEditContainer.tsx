import React, { lazy, useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import getFileContents from '@/api/server/files/getFileContents';
import useRouter from 'use-react-router';
import { Actions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import saveFileContents from '@/api/server/files/saveFileContents';
import FileManagerBreadcrumbs from '@/components/server/files/FileManagerBreadcrumbs';

const LazyAceEditor = lazy(() => import(/* webpackChunkName: "editor" */'@/components/elements/AceEditor'));

export default () => {
    const { location: { hash } } = useRouter();
    const [ loading, setLoading ] = useState(true);
    const [ content, setContent ] = useState('');

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const addError = useStoreState((state: Actions<ApplicationStore>) => state.flashes.addError);

    let fetchFileContent: null | (() => Promise<string>) = null;

    useEffect(() => {
        getFileContents(uuid, hash.replace(/^#/, ''))
            .then(setContent)
            .catch(error => console.error(error))
            .then(() => setLoading(false));
    }, [ uuid, hash ]);

    const save = (e: React.MouseEvent<HTMLButtonElement>) => {
        if (!fetchFileContent) {
            return;
        }

        setLoading(true);
        fetchFileContent()
            .then(content => {
                return saveFileContents(uuid, hash.replace(/^#/, ''), content);
            })
            .catch(error => {
                console.error(error);
                addError({ message: httpErrorToHuman(error), key: 'files' });
            })
            .then(() => setLoading(false));
    };

    return (
        <div className={'mt-10 mb-4'}>
            <FileManagerBreadcrumbs withinFileEditor={true}/>
            <div className={'relative'}>
                <SpinnerOverlay visible={loading}/>
                <LazyAceEditor
                    initialModePath={hash.replace(/^#/, '') || 'plain_text'}
                    initialContent={content}
                    fetchContent={value => {
                        fetchFileContent = value;
                    }}
                    onContentSaved={() => null}
                />
            </div>
            <div className={'flex justify-end mt-4'}>
                <button className={'btn btn-primary btn-sm'} onClick={save}>
                    Save Content
                </button>
            </div>
        </div>
    );
};
