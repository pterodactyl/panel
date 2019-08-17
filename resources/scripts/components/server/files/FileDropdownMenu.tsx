import React, { createRef, useEffect, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faEllipsisH } from '@fortawesome/free-solid-svg-icons/faEllipsisH';
import { CSSTransition } from 'react-transition-group';
import { faPencilAlt } from '@fortawesome/free-solid-svg-icons/faPencilAlt';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons/faTrashAlt';
import { faFileDownload } from '@fortawesome/free-solid-svg-icons/faFileDownload';
import { faCopy } from '@fortawesome/free-solid-svg-icons/faCopy';
import { faLevelUpAlt } from '@fortawesome/free-solid-svg-icons/faLevelUpAlt';
import RenameFileModal from '@/components/server/files/RenameFileModal';
import { ServerContext } from '@/state/server';
import { join } from 'path';
import deleteFile from '@/api/server/files/deleteFile';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import copyFile from '@/api/server/files/copyFile';
import { httpErrorToHuman } from '@/api/http';

type ModalType = 'rename' | 'move';

export default ({ uuid }: { uuid: string }) => {
    const menu = createRef<HTMLDivElement>();
    const menuButton = createRef<HTMLDivElement>();
    const [ menuVisible, setMenuVisible ] = useState(false);
    const [ showSpinner, setShowSpinner ] = useState(false);
    const [ modal, setModal ] = useState<ModalType | null>(null);
    const [ posX, setPosX ] = useState(0);

    const server = ServerContext.useStoreState(state => state.server.data!);
    const file = ServerContext.useStoreState(state => state.files.contents.find(file => file.uuid === uuid));
    const directory = ServerContext.useStoreState(state => state.files.directory);
    const { removeFile, getDirectoryContents } = ServerContext.useStoreActions(actions => actions.files);

    if (!file) {
        return null;
    }

    const windowListener = (e: MouseEvent) => {
        if (e.button === 2 || !menuVisible || !menu.current) {
            return;
        }

        if (e.target === menu.current || menu.current.contains(e.target as Node)) {
            return;
        }

        if (e.target !== menu.current && !menu.current.contains(e.target as Node)) {
            setMenuVisible(false);
        }
    };

    const doDeletion = () => {
        setShowSpinner(true);
        deleteFile(server.uuid, join(directory, file.name))
            .then(() => removeFile(uuid))
            .catch(error => {
                console.error('Error while attempting to delete a file.', error);
                setShowSpinner(false);
            });
    };

    const doCopy = () => {
        setShowSpinner(true);
        copyFile(server.uuid, join(directory, file.name))
            .then(() => getDirectoryContents(directory))
            .catch(error => {
                console.error('Error while attempting to copy file.', error);
                alert(httpErrorToHuman(error));
                setShowSpinner(false);
            });
    };

    useEffect(() => {
        menuVisible
            ? document.addEventListener('click', windowListener)
            : document.removeEventListener('click', windowListener);

        if (menuVisible && menu.current) {
            menu.current.setAttribute(
                'style', `margin-top: -0.35rem; left: ${Math.round(posX - menu.current.clientWidth)}px`,
            );
        }
    }, [ menuVisible ]);

    useEffect(() => () => {
        document.removeEventListener('click', windowListener);
    }, []);

    return (
        <div key={`dropdown:${file.uuid}`}>
            <div
                ref={menuButton}
                className={'p-3 hover:text-white'}
                onClick={e => {
                    e.preventDefault();
                    if (!menuVisible) {
                        setPosX(e.clientX);
                    }
                    setModal(null);
                    setMenuVisible(!menuVisible);
                }}
            >
                <FontAwesomeIcon icon={faEllipsisH}/>
                <RenameFileModal
                    file={file}
                    visible={modal === 'rename' || modal === 'move'}
                    useMoveTerminology={modal === 'move'}
                    onDismissed={() => {
                        setModal(null);
                        setMenuVisible(false);
                    }}
                />
                <SpinnerOverlay visible={showSpinner} fixed={true} size={'large'}/>
            </div>
            <CSSTransition timeout={250} in={menuVisible} unmountOnExit={true} classNames={'fade'}>
                <div
                    ref={menu}
                    onClick={e => { e.stopPropagation(); setMenuVisible(false); }}
                    className={'absolute bg-white p-2 rounded border border-neutral-700 shadow-lg text-neutral-500 min-w-48'}
                >
                    <div
                        onClick={() => setModal('rename')}
                        className={'hover:text-neutral-700 p-2 flex items-center hover:bg-neutral-100 rounded'}
                    >
                        <FontAwesomeIcon icon={faPencilAlt} className={'text-xs'}/>
                        <span className={'ml-2'}>Rename</span>
                    </div>
                    <div
                        onClick={() => setModal('move')}
                        className={'hover:text-neutral-700 p-2 flex items-center hover:bg-neutral-100 rounded'}
                    >
                        <FontAwesomeIcon icon={faLevelUpAlt} className={'text-xs'}/>
                        <span className={'ml-2'}>Move</span>
                    </div>
                    <div
                        onClick={() => doCopy()}
                        className={'hover:text-neutral-700 p-2 flex items-center hover:bg-neutral-100 rounded'}
                    >
                        <FontAwesomeIcon icon={faCopy} className={'text-xs'}/>
                        <span className={'ml-2'}>Copy</span>
                    </div>
                    <div className={'hover:text-neutral-700 p-2 flex items-center hover:bg-neutral-100 rounded'}>
                        <FontAwesomeIcon icon={faFileDownload} className={'text-xs'}/>
                        <span className={'ml-2'}>Download</span>
                    </div>
                    <div
                        onClick={() => doDeletion()}
                        className={'hover:text-red-700 p-2 flex items-center hover:bg-red-100 rounded'}
                    >
                        <FontAwesomeIcon icon={faTrashAlt} className={'text-xs'}/>
                        <span className={'ml-2'}>Delete</span>
                    </div>
                </div>
            </CSSTransition>
        </div>
    );
};
