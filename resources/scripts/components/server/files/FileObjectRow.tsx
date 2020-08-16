import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFileAlt, faFileArchive, faFileImport, faFolder } from '@fortawesome/free-solid-svg-icons';
import { bytesToHuman, cleanDirectoryPath } from '@/helpers';
import { differenceInHours, format, formatDistanceToNow } from 'date-fns';
import React, { memo } from 'react';
import { FileObject } from '@/api/server/files/loadDirectory';
import FileDropdownMenu from '@/components/server/files/FileDropdownMenu';
import { ServerContext } from '@/state/server';
import { NavLink, useHistory, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import isEqual from 'react-fast-compare';
import styled from 'styled-components/macro';
import SelectFileCheckbox from '@/components/server/files/SelectFileCheckbox';

const Row = styled.div`
    ${tw`flex bg-neutral-700 rounded-sm mb-px text-sm hover:text-neutral-100 cursor-pointer items-center no-underline hover:bg-neutral-600`};
`;

const FileObjectRow = ({ file }: { file: FileObject }) => {
    const directory = ServerContext.useStoreState(state => state.files.directory);

    const history = useHistory();
    const match = useRouteMatch();

    const onRowClick = (e: React.MouseEvent<HTMLAnchorElement, MouseEvent>) => {
        // Don't rely on the onClick to work with the generated URL. Because of the way this
        // component re-renders you'll get redirected into a nested directory structure since
        // it'll cause the directory variable to update right away when you click.
        //
        // Just trust me future me, leave this be.
        if (!file.isFile) {
            e.preventDefault();
            history.push(`#${cleanDirectoryPath(`${directory}/${file.name}`)}`);
        }
    };

    return (
        <Row
            key={file.name}
            onContextMenu={e => {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent(`pterodactyl:files:ctx:${file.key}`, { detail: e.clientX }));
            }}
        >
            <SelectFileCheckbox name={file.name}/>
            <NavLink
                to={`${match.url}/${file.isFile ? 'edit/' : ''}#${cleanDirectoryPath(`${directory}/${file.name}`)}`}
                css={tw`flex flex-1 text-neutral-300 no-underline p-3`}
                onClick={onRowClick}
            >
                <div css={tw`flex-none self-center text-neutral-400 mr-4 text-lg pl-3 ml-6`}>
                    {file.isFile ?
                        <FontAwesomeIcon icon={file.isSymlink ? faFileImport : file.isArchiveType() ? faFileArchive : faFileAlt}/>
                        :
                        <FontAwesomeIcon icon={faFolder}/>
                    }
                </div>
                <div css={tw`flex-1`}>
                    {file.name}
                </div>
                {file.isFile &&
                <div css={tw`w-1/6 text-right mr-4`}>
                    {bytesToHuman(file.size)}
                </div>
                }
                <div
                    css={tw`w-1/5 text-right mr-4`}
                    title={file.modifiedAt.toString()}
                >
                    {Math.abs(differenceInHours(file.modifiedAt, new Date())) > 48 ?
                        format(file.modifiedAt, 'MMM do, yyyy h:mma')
                        :
                        formatDistanceToNow(file.modifiedAt, { addSuffix: true })
                    }
                </div>
            </NavLink>
            <FileDropdownMenu file={file}/>
        </Row>
    );
};

export default memo(FileObjectRow, (prevProps, nextProps) => isEqual(prevProps.file, nextProps.file));
