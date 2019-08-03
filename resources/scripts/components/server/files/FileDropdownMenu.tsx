import React, { createRef, useEffect, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faEllipsisH } from '@fortawesome/free-solid-svg-icons/faEllipsisH';
import { FileObject } from '@/api/server/files/loadDirectory';
import { CSSTransition } from 'react-transition-group';
import { faPencilAlt } from '@fortawesome/free-solid-svg-icons/faPencilAlt';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons/faTrashAlt';
import { faFileDownload } from '@fortawesome/free-solid-svg-icons/faFileDownload';
import { faCopy } from '@fortawesome/free-solid-svg-icons/faCopy';
import { faLevelUpAlt } from '@fortawesome/free-solid-svg-icons/faLevelUpAlt';
import RenameFileModal from '@/components/server/files/RenameFileModal';

type ModalType = 'rename' | 'move';

export default ({ file }: { file: FileObject }) => {
    const menu = createRef<HTMLDivElement>();
    const [ visible, setVisible ] = useState(false);
    const [ modal, setModal ] = useState<ModalType | null>(null);
    const [ posX, setPosX ] = useState(0);

    const windowListener = (e: MouseEvent) => {
        if (e.button === 2 || !visible || !menu.current) {
            return;
        }

        if (e.target === menu.current || menu.current.contains(e.target as Node)) {
            return;
        }

        if (e.target !== menu.current && !menu.current.contains(e.target as Node)) {
            setVisible(false);
        }
    };

    useEffect(() => {
        visible
            ? document.addEventListener('click', windowListener)
            : document.removeEventListener('click', windowListener);

        if (visible && menu.current) {
            menu.current.setAttribute(
                'style', `margin-top: -0.35rem; left: ${Math.round(posX - menu.current.clientWidth)}px`,
            );
        }
    }, [ visible ]);

    useEffect(() => () => {
        document.removeEventListener('click', windowListener);
    }, []);

    return (
        <div>
            <div
                className={'p-3 hover:text-white'}
                onClick={e => {
                    e.preventDefault();
                    if (!visible) {
                        setPosX(e.clientX);
                    } else if (visible) {
                        setModal(null);
                    }
                    setVisible(!visible);
                }}
            >
                <FontAwesomeIcon icon={faEllipsisH}/>
            </div>
            {visible &&
                <React.Fragment>
                    <RenameFileModal file={file} visible={modal === 'rename'} onDismissed={() => setModal(null)}/>
                </React.Fragment>
            }
            <CSSTransition timeout={250} in={visible} unmountOnExit={true} classNames={'fade'}>
                <div
                    className={'absolute bg-white p-2 rounded border border-neutral-700 shadow-lg text-neutral-500 min-w-48'}
                    ref={menu}
                >
                    <div
                        className={'hover:text-neutral-700 p-2 flex items-center hover:bg-neutral-100 rounded'}
                        onClick={() => setModal('rename')}
                    >
                        <FontAwesomeIcon icon={faPencilAlt} className={'text-xs'}/>
                        <span className={'ml-2'}>Rename</span>
                    </div>
                    <div className={'hover:text-neutral-700 p-2 flex items-center hover:bg-neutral-100 rounded'}>
                        <FontAwesomeIcon icon={faLevelUpAlt} className={'text-xs'}/>
                        <span className={'ml-2'}>Move</span>
                    </div>
                    <div className={'hover:text-neutral-700 p-2 flex items-center hover:bg-neutral-100 rounded'}>
                        <FontAwesomeIcon icon={faCopy} className={'text-xs'}/>
                        <span className={'ml-2'}>Copy</span>
                    </div>
                    <div className={'hover:text-neutral-700 p-2 flex items-center hover:bg-neutral-100 rounded'}>
                        <FontAwesomeIcon icon={faFileDownload} className={'text-xs'}/>
                        <span className={'ml-2'}>Download</span>
                    </div>
                    <div className={'hover:text-red-700 p-2 flex items-center hover:bg-red-100 rounded'}>
                        <FontAwesomeIcon icon={faTrashAlt} className={'text-xs'}/>
                        <span className={'ml-2'}>Delete</span>
                    </div>
                </div>
            </CSSTransition>
        </div>
    );
};
