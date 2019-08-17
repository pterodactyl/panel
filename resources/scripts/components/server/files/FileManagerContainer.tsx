import React, { useEffect, useState } from 'react';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import { CSSTransition } from 'react-transition-group';
import Spinner from '@/components/elements/Spinner';
import FileObjectRow from '@/components/server/files/FileObjectRow';

export default () => {
    const [ loading, setLoading ] = useState(true);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const { contents: files, directory } = ServerContext.useStoreState(state => state.files);
    const { setDirectory, getDirectoryContents } = ServerContext.useStoreActions(actions => actions.files);

    const load = () => {
        setLoading(true);
        clearFlashes();

        getDirectoryContents(window.location.hash.replace(/^#(\/)*/, '/'))
            .then(() => setLoading(false))
            .catch(error => {
                console.error(error.message, { error });
                addError({ message: httpErrorToHuman(error), key: 'files' });
            });
    };

    const breadcrumbs = (): { name: string; path?: string }[] => directory.split('/')
        .filter(directory => !!directory)
        .map((directory, index, dirs) => {
            if (index === dirs.length - 1) {
                return { name: directory };
            }

            return { name: directory, path: `/${dirs.slice(0, index + 1).join('/')}` };
        });

    useEffect(() => load(), [ directory ]);

    return (
        <div className={'my-10 mb-6'}>
            <FlashMessageRender byKey={'files'} className={'mb-4'}/>
            <React.Fragment>
                <div className={'flex items-center text-sm mb-4 text-neutral-500'}>
                    /<span className={'px-1 text-neutral-300'}>home</span>/
                    <a
                        href={'#'}
                        onClick={() => setDirectory('/')}
                        className={'px-1 text-neutral-200 no-underline hover:text-neutral-100'}
                    >
                        container
                    </a>/
                    {
                        breadcrumbs().map((crumb, index) => (
                            crumb.path ?
                                <React.Fragment key={index}>
                                    <a
                                        href={`#${crumb.path}`}
                                        onClick={() => setDirectory(crumb.path!)}
                                        className={'px-1 text-neutral-200 no-underline hover:text-neutral-100'}
                                    >
                                        {crumb.name}
                                    </a>/
                                </React.Fragment>
                                :
                                <span key={index} className={'px-1 text-neutral-300'}>{crumb.name}</span>
                        ))
                    }
                </div>
                {
                    loading ?
                        <Spinner size={'large'} centered={true}/>
                        :
                        !files.length ?
                            <p className={'text-sm text-neutral-600 text-center'}>
                                This directory seems to be empty.
                            </p>
                            :
                            <CSSTransition classNames={'fade'} timeout={250} appear={true} in={true}>
                                <div>
                                    {
                                        files.map(file => (
                                            <FileObjectRow key={file.uuid} file={file}/>
                                        ))
                                    }
                                </div>
                            </CSSTransition>
                }
            </React.Fragment>
        </div>
    );
};
