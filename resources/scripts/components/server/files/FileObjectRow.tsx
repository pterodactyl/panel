import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFileImport } from '@fortawesome/free-solid-svg-icons/faFileImport';
import { faFileAlt } from '@fortawesome/free-solid-svg-icons/faFileAlt';
import { faFolder } from '@fortawesome/free-solid-svg-icons/faFolder';
import { bytesToHuman, cleanDirectoryPath } from '@/helpers';
import { differenceInHours, format, formatDistanceToNow } from 'date-fns';
import React from 'react';
import { FileObject } from '@/api/server/files/loadDirectory';
import FileDropdownMenu from '@/components/server/files/FileDropdownMenu';
import { ServerContext } from '@/state/server';
import { NavLink } from 'react-router-dom';
import useRouter from 'use-react-router';

export default ({ file }: { file: FileObject }) => {
    const directory = ServerContext.useStoreState(state => state.files.directory);
    const setDirectory = ServerContext.useStoreActions(actions => actions.files.setDirectory);
    const { match, history } = useRouter();

    return (
        <div
            key={file.name}
            className={`
                flex bg-neutral-700 rounded-sm mb-px text-sm
                hover:text-neutral-100 cursor-pointer items-center no-underline hover:bg-neutral-600
            `}
        >
            <NavLink
                to={`${match.url}/${file.isFile ? 'edit/' : ''}#${cleanDirectoryPath(`${directory}/${file.name}`)}`}
                className={'flex flex-1 text-neutral-300 no-underline p-3'}
                onClick={e => {
                    // Don't rely on the onClick to work with the generated URL. Because of the way this
                    // component re-renders you'll get redirected into a nested directory structure since
                    // it'll cause the directory variable to update right away when you click.
                    //
                    // Just trust me future me, leave this be.
                    if (!file.isFile) {
                        e.preventDefault();

                        history.push(`#${cleanDirectoryPath(`${directory}/${file.name}`)}`);
                        setDirectory(`${directory}/${file.name}`);
                    }
                }}
            >
                <div className={'flex-none text-neutral-400 mr-4 text-lg pl-3'}>
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
                        formatDistanceToNow(file.modifiedAt, { addSuffix: true })
                    }
                </div>
            </NavLink>
            <FileDropdownMenu uuid={file.uuid}/>
        </div>
    );
};
