import React, { useEffect, useState } from 'react';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ServerContext } from '@/state/server';
import loadDirectory, { FileObject } from '@/api/server/files/loadDirectory';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFolder } from '@fortawesome/free-solid-svg-icons/faFolder';
import { faFileAlt } from '@fortawesome/free-solid-svg-icons/faFileAlt';
import format from 'date-fns/format';
import differenceInHours from 'date-fns/difference_in_hours';
import distanceInWordsToNow from 'date-fns/distance_in_words_to_now';
import { bytesToHuman } from '@/helpers';
import { CSSTransition } from 'react-transition-group';
import { Link } from 'react-router-dom';
import Spinner from '@/components/elements/Spinner';
import { faEllipsisH } from '@fortawesome/free-solid-svg-icons/faEllipsisH';
import { faFile } from '@fortawesome/free-solid-svg-icons/faFile';
import { faFileImport } from '@fortawesome/free-solid-svg-icons/faFileImport';

export default () => {
    const [ loading, setLoading ] = useState(true);
    const [ files, setFiles ] = useState<FileObject[]>([]);
    const server = ServerContext.useStoreState(state => state.server.data!);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const currentDirectory = window.location.hash.replace(/^#(\/)+/, '/');

    const load = () => {
        setLoading(true);
        clearFlashes();
        loadDirectory(server.uuid, currentDirectory)
            .then(files => {
                setFiles(files);
                setLoading(false);
            })
            .catch(error => {
                if (error.response && error.response.status === 404) {
                    window.location.hash = '#/';
                    return;
                }

                console.error(error.message, { error });
                addError({ message: httpErrorToHuman(error), key: 'files' });
            });
    };

    const breadcrumbs = (): { name: string; path?: string }[] => currentDirectory.split('/')
        .filter(directory => !!directory)
        .map((directory, index, dirs) => {
            if (index === dirs.length - 1) {
                return { name: directory };
            }

            return { name: directory, path: `/${dirs.slice(0, index + 1).join('/')}` };
        });

    useEffect(() => {
        load();
    }, [ window.location.hash ]);

    return (
        <div className={'my-10 mb-6'}>
            <FlashMessageRender byKey={'files'}/>
            <React.Fragment>
                <div className={'flex items-center text-sm mb-4 text-neutral-500'}>
                    /<span className={'px-1 text-neutral-300'}>home</span>/
                    <Link to={'#'} className={'px-1 text-neutral-200 no-underline hover:text-neutral-100'}>
                        container
                    </Link>/
                    {
                        breadcrumbs().map((crumb, index) => (
                            crumb.path ?
                                <React.Fragment key={index}>
                                    <Link
                                        to={`#${crumb.path}`}
                                        className={'px-1 text-neutral-200 no-underline hover:text-neutral-100'}
                                    >
                                        {crumb.name}
                                    </Link>/
                                </React.Fragment>
                                :
                                <span key={index} className={'px-1 text-neutral-300'}>{crumb.name}</span>
                        ))
                    }
                </div>
                {
                    loading ?
                        <Spinner large={true} centered={true}/>
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
                                            <a
                                                key={file.name}
                                                href={file.isFile ? undefined : `#${currentDirectory}/${file.name}`}
                                                className={`
                                                    flex px-4 py-3 bg-neutral-700 text-neutral-300 rounded-sm mb-px text-sm
                                                    hover:text-neutral-100 cursor-pointer items-center no-underline hover:bg-neutral-600
                                                `}
                                                onClick={(e) => {
                                                    if (file.isFile) {
                                                        return e.preventDefault();
                                                    }
                                                }}
                                            >
                                                <div className={'flex-none text-neutral-400 mr-4 text-lg'}>
                                                    {file.isFile ?
                                                        <FontAwesomeIcon icon={file.isSymlink ? faFileImport : faFileAlt}/>
                                                        :
                                                        <FontAwesomeIcon icon={faFolder}/>
                                                    }
                                                </div>
                                                <div className={'flex-1'}>
                                                    {file.name}
                                                </div>
                                                {file.isFile &&
                                                <div className={'w-1/6 text-right mr-4'}>
                                                    {bytesToHuman(file.size)}
                                                </div>
                                                }
                                                <div
                                                    className={'w-1/5 text-right mr-4'}
                                                    title={file.modifiedAt.toString()}
                                                >
                                                    {Math.abs(differenceInHours(file.modifiedAt, new Date())) > 48 ?
                                                        format(file.modifiedAt, 'MMM Do, YYYY h:mma')
                                                        :
                                                        distanceInWordsToNow(file.modifiedAt, { includeSeconds: true })
                                                    }
                                                </div>
                                                <div>
                                                    <FontAwesomeIcon icon={faEllipsisH}/>
                                                </div>
                                            </a>
                                        ))
                                    }
                                </div>
                            </CSSTransition>
                }
            </React.Fragment>
        </div>
    );
};
