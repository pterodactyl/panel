import React, { useEffect, useState } from 'react';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import { CSSTransition } from 'react-transition-group';
import Spinner from '@/components/elements/Spinner';
import FileObjectRow from '@/components/server/files/FileObjectRow';
import FileManagerBreadcrumbs from '@/components/server/files/FileManagerBreadcrumbs';
import { FileObject } from '@/api/server/files/loadDirectory';
import NewDirectoryButton from '@/components/server/files/NewDirectoryButton';
import { Link } from 'react-router-dom';
import Can from '@/components/elements/Can';
import PageContentBlock from '@/components/elements/PageContentBlock';

const sortFiles = (files: FileObject[]): FileObject[] => {
    return files.sort((a, b) => a.name.localeCompare(b.name))
        .sort((a, b) => a.isFile === b.isFile ? 0 : (a.isFile ? 1 : -1));
};

export default () => {
    const [ loading, setLoading ] = useState(true);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const { id } = ServerContext.useStoreState(state => state.server.data!);
    const { contents: files } = ServerContext.useStoreState(state => state.files);
    const { getDirectoryContents } = ServerContext.useStoreActions(actions => actions.files);

    useEffect(() => {
        setLoading(true);
        clearFlashes();

        getDirectoryContents(window.location.hash)
            .then(() => setLoading(false))
            .catch(error => {
                console.error(error.message, { error });
                addError({ message: httpErrorToHuman(error), key: 'files' });
            });
    }, []);

    return (
        <PageContentBlock>
            <FlashMessageRender byKey={'files'} className={'mb-4'}/>
            <React.Fragment>
                <FileManagerBreadcrumbs/>
                {
                    loading ?
                        <Spinner size={'large'} centered={true}/>
                        :
                        <React.Fragment>
                            {!files.length ?
                                <p className={'text-sm text-neutral-400 text-center'}>
                                    This directory seems to be empty.
                                </p>
                                :
                                <CSSTransition classNames={'fade'} timeout={250} appear={true} in={true}>
                                    <React.Fragment>
                                        <div>
                                            {files.length > 250 ?
                                                <React.Fragment>
                                                    <div className={'rounded bg-yellow-400 mb-px p-3'}>
                                                        <p className={'text-yellow-900 text-sm text-center'}>
                                                            This directory is too large to display in the browser,
                                                            limiting the output to the first 250 files.
                                                        </p>
                                                    </div>
                                                    {
                                                        sortFiles(files.slice(0, 250)).map(file => (
                                                            <FileObjectRow key={file.uuid} file={file}/>
                                                        ))
                                                    }
                                                </React.Fragment>
                                                :
                                                sortFiles(files).map(file => (
                                                    <FileObjectRow key={file.uuid} file={file}/>
                                                ))
                                            }
                                        </div>
                                    </React.Fragment>
                                </CSSTransition>
                            }
                            <Can action={'file.create'}>
                                <div className={'flex justify-end mt-8'}>
                                    <NewDirectoryButton/>
                                    <Link
                                        to={`/server/${id}/files/new${window.location.hash}`}
                                        className={'btn btn-sm btn-primary'}
                                    >
                                        New File
                                    </Link>
                                </div>
                            </Can>
                        </React.Fragment>
                }
            </React.Fragment>
        </PageContentBlock>
    );
};
