import React, { useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { httpErrorToHuman } from '@/api/http';
import { CSSTransition } from 'react-transition-group';
import Spinner from '@/components/elements/Spinner';
import FileObjectRow from '@/components/server/files/FileObjectRow';
import FileManagerBreadcrumbs from '@/components/server/files/FileManagerBreadcrumbs';
import { FileObject } from '@/api/server/files/loadDirectory';
import NewDirectoryButton from '@/components/server/files/NewDirectoryButton';
import { Link, useLocation } from 'react-router-dom';
import Can from '@/components/elements/Can';
import PageContentBlock from '@/components/elements/PageContentBlock';
import ServerError from '@/components/screens/ServerError';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import useServer from '@/plugins/useServer';
import { ServerContext } from '@/state/server';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import MassActionsBar from '@/components/server/files/MassActionsBar';

const sortFiles = (files: FileObject[]): FileObject[] => {
    return files.sort((a, b) => a.name.localeCompare(b.name))
        .sort((a, b) => a.isFile === b.isFile ? 0 : (a.isFile ? 1 : -1));
};

export default () => {
    const { id, name: serverName } = useServer();
    const { hash } = useLocation();
    const { data: files, error, mutate } = useFileManagerSwr();

    const setDirectory = ServerContext.useStoreActions(actions => actions.files.setDirectory);
    const setSelectedFiles = ServerContext.useStoreActions(actions => actions.files.setSelectedFiles);

    useEffect(() => {
        setSelectedFiles([]);
        setDirectory(hash.length > 0 ? hash : '/');
    }, [ hash ]);

    if (error) {
        return (
            <ServerError message={httpErrorToHuman(error)} onRetry={() => mutate()}/>
        );
    }

    return (
        <PageContentBlock showFlashKey={'files'}>
            <Helmet>
                <title> {serverName} | File Manager </title>
            </Helmet>
            <FileManagerBreadcrumbs/>
            {
                !files ?
                    <Spinner size={'large'} centered/>
                    :
                    <>
                        {!files.length ?
                            <p css={tw`text-sm text-neutral-400 text-center`}>
                                This directory seems to be empty.
                            </p>
                            :
                            <CSSTransition classNames={'fade'} timeout={150} appear in>
                                <div>
                                    {files.length > 250 &&
                                    <div css={tw`rounded bg-yellow-400 mb-px p-3`}>
                                        <p css={tw`text-yellow-900 text-sm text-center`}>
                                            This directory is too large to display in the browser,
                                            limiting the output to the first 250 files.
                                        </p>
                                    </div>
                                    }
                                    {
                                        sortFiles(files.slice(0, 250)).map(file => (
                                            <FileObjectRow key={file.key} file={file}/>
                                        ))
                                    }
                                    <MassActionsBar/>
                                </div>
                            </CSSTransition>
                        }
                        <Can action={'file.create'}>
                            <div css={tw`flex justify-end mt-8`}>
                                <NewDirectoryButton/>
                                <Button
                                    // @ts-ignore
                                    as={Link}
                                    to={`/server/${id}/files/new${window.location.hash}`}
                                >
                                    New File
                                </Button>
                            </div>
                        </Can>
                    </>
            }
        </PageContentBlock>
    );
};
