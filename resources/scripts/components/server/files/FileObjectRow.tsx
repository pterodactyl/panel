import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFileAlt, faFileArchive, faFileImport, faFolder } from '@fortawesome/free-solid-svg-icons';
import { bytesToHuman, encodePathSegments } from '@/helpers';
import { differenceInHours, format, formatDistanceToNow } from 'date-fns';
import React, { memo } from 'react';
import { FileObject } from '@/api/server/files';
import FileDropdownMenu from '@/components/server/files/FileDropdownMenu';
import { ServerContext } from '@/state/server';
import { NavLink, useRouteMatch } from 'react-router-dom';
import tw, { styled } from 'twin.macro';
import isEqual from 'react-fast-compare';
import SelectFileCheckbox from '@/components/server/files/SelectFileCheckbox';
import { usePermissions } from '@/plugins/usePermissions';
import { join } from 'path';

const Row = styled.div`
  ${tw`flex items-center w-full h-10 px-3 rounded-sm cursor-pointer bg-neutral-700 hover:bg-neutral-600 mb-px`};
`;

const Clickable: React.FC<{ file: FileObject }> = memo(({ file, children }) => {
    const [ canReadContents ] = usePermissions([ 'file.read-content' ]);
    const directory = ServerContext.useStoreState(state => state.files.directory);

    const match = useRouteMatch();

    return (
        (!canReadContents || (file.isFile && !file.isEditable())) ?
            <div css={tw`flex items-center w-full h-full`}>
                {children}
            </div>
            :
            <NavLink
                to={`${match.url}${file.isFile ? '/edit' : ''}#${encodePathSegments(join(directory, file.name))}`}
                css={tw`flex items-center w-full h-full`}
                draggable={false}
            >
                {children}
            </NavLink>
    );
}, isEqual);

const FileObjectRow = ({ file }: { file: FileObject }) => (
    <Row
        key={file.name}
        onContextMenu={e => {
            e.preventDefault();
            window.dispatchEvent(new CustomEvent(`pterodactyl:files:ctx:${file.key}`, { detail: e.clientX }));
        }}
        css={tw`h-10`}
    >
        <div css={tw`flex mr-4`}>
            <SelectFileCheckbox name={file.name}/>
        </div>
        <Clickable file={file}>
            <div css={tw`flex flex-row items-center justify-center w-5 text-neutral-400 mr-2`}>
                {file.isFile ?
                    <FontAwesomeIcon icon={file.isSymlink ? faFileImport : file.isArchiveType() ? faFileArchive : faFileAlt}/>
                    :
                    <FontAwesomeIcon icon={faFolder}/>
                }
            </div>
            <div css={tw`block`}>
                <span css={tw`text-sm font-normal leading-none text-neutral-300`}>{file.name}</span>
            </div>

            <div css={tw`hidden w-24 ml-auto sm:flex`}>
                <span css={tw`ml-auto text-sm font-normal leading-none text-right text-neutral-300`}>
                    {bytesToHuman(file.size)}
                </span>
            </div>

            <div css={tw`hidden w-48 md:flex`}>
                <span
                    css={tw`ml-auto text-sm font-normal leading-none text-right text-neutral-300`}
                    title={file.modifiedAt.toString()}
                >
                    {Math.abs(differenceInHours(file.modifiedAt, new Date())) > 48 ?
                        format(file.modifiedAt, 'MMM do, yyyy h:mma')
                        :
                        formatDistanceToNow(file.modifiedAt, { addSuffix: true })
                    }
                </span>
            </div>
        </Clickable>
        <FileDropdownMenu file={file}/>
    </Row>
);

export default memo(FileObjectRow, (prevProps, nextProps) => {
    /* eslint-disable @typescript-eslint/no-unused-vars */
    const { isArchiveType, isEditable, ...prevFile } = prevProps.file;
    const { isArchiveType: nextIsArchiveType, isEditable: nextIsEditable, ...nextFile } = nextProps.file;
    /* eslint-enable @typescript-eslint/no-unused-vars */

    return isEqual(prevFile, nextFile);
});
