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
import { useParams } from 'react-router';
import FileNameModal from '@/components/server/files/FileNameModal';

const LazyAceEditor = lazy(() => import(/* webpackChunkName: "editor" */'@/components/elements/AceEditor'));

export default () => {
    const { action } = useParams();
    const { history, location: { hash } } = useRouter();
    const [ loading, setLoading ] = useState(action === 'edit');
    const [ content, setContent ] = useState('');
    const [ modalVisible, setModalVisible ] = useState(false);

    const { id, uuid } = ServerContext.useStoreState(state => state.server.data!);
    const addError = useStoreState((state: Actions<ApplicationStore>) => state.flashes.addError);

    let fetchFileContent: null | (() => Promise<string>) = null;

    if (action !== 'new') {
        useEffect(() => {
            getFileContents(uuid, hash.replace(/^#/, ''))
                .then(setContent)
                .catch(error => console.error(error))
                .then(() => setLoading(false));
        }, [ uuid, hash ]);
    }

    const save = (name?: string) => {
        if (!fetchFileContent) {
            return;
        }

        setLoading(true);
        fetchFileContent()
            .then(content => {
                return saveFileContents(uuid, name || hash.replace(/^#/, ''), content);
            })
            .then(() => {
                if (name) {
                    history.push(`/server/${id}/files/edit#/${name}`);
                    return;
                }

                return Promise.resolve();
            })
            .catch(error => {
                console.error(error);
                addError({ message: httpErrorToHuman(error), key: 'files' });
            })
            .then(() => setLoading(false));
    };

    return (
        <div className={'mt-10 mb-4'}>
            <FileManagerBreadcrumbs withinFileEditor={true} isNewFile={action !== 'edit'}/>
            <FileNameModal
                visible={modalVisible}
                onDismissed={() => setModalVisible(false)}
                onFileNamed={(name) => {
                    setModalVisible(false);
                    save(name);
                }}
            />
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
                {action === 'edit' ?
                    <button className={'btn btn-primary btn-sm'} onClick={() => save()}>
                        Save Content
                    </button>
                    :
                    <button className={'btn btn-primary btn-sm'} onClick={() => setModalVisible(true)}>
                        Create File
                    </button>
                }
            </div>
        </div>
    );
};
