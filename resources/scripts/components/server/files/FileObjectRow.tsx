import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFileAlt, faFileArchive, faFileImport, faFolder } from '@fortawesome/free-solid-svg-icons';
import { differenceInHours, format, formatDistanceToNow } from 'date-fns';
import type { ReactNode } from 'react';
import { memo } from 'react';
import isEqual from 'react-fast-compare';
import { NavLink } from 'react-router-dom';
import tw from 'twin.macro';
import { join } from 'pathe';

import type { FileObject } from '@/api/server/files/loadDirectory';
import FileDropdownMenu from '@/components/server/files/FileDropdownMenu';
import SelectFileCheckbox from '@/components/server/files/SelectFileCheckbox';
import { encodePathSegments } from '@/helpers';
import { bytesToString } from '@/lib/formatters';
import { usePermissions } from '@/plugins/usePermissions';
import { ServerContext } from '@/state/server';
import styles from './style.module.css';

function Clickable({ file, children }: { file: FileObject; children: ReactNode }) {
    const [canReadContents] = usePermissions(['file.read-content']);
    const id = ServerContext.useStoreState(state => state.server.data!.id);
    const directory = ServerContext.useStoreState(state => state.files.directory);

    return !canReadContents || (file.isFile && !file.isEditable()) ? (
        <div className={styles.details}>{children}</div>
    ) : (
        <NavLink
            className={styles.details}
            to={`/server/${id}/files${file.isFile ? '/edit' : '#'}${encodePathSegments(join(directory, file.name))}`}
        >
            {children}
        </NavLink>
    );
}

const MemoizedClickable = memo(Clickable, isEqual);

function FileObjectRow({ file }: { file: FileObject }) {
    return (
        <div
            className={styles.file_row}
            key={file.name}
            onContextMenu={e => {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent(`pterodactyl:files:ctx:${file.key}`, { detail: e.clientX }));
            }}
        >
            <SelectFileCheckbox name={file.name} />
            <MemoizedClickable file={file}>
                <div css={tw`flex-none text-neutral-400 ml-6 mr-4 text-lg pl-3`}>
                    {file.isFile ? (
                        <FontAwesomeIcon
                            icon={file.isSymlink ? faFileImport : file.isArchiveType() ? faFileArchive : faFileAlt}
                        />
                    ) : (
                        <FontAwesomeIcon icon={faFolder} />
                    )}
                </div>
                <div css={tw`flex-1 truncate`}>{file.name}</div>
                {file.isFile && <div css={tw`w-1/6 text-right mr-4 hidden sm:block`}>{bytesToString(file.size)}</div>}
                <div css={tw`w-1/5 text-right mr-4 hidden md:block`} title={file.modifiedAt.toString()}>
                    {Math.abs(differenceInHours(file.modifiedAt, new Date())) > 48
                        ? format(file.modifiedAt, 'MMM do, yyyy h:mma')
                        : formatDistanceToNow(file.modifiedAt, { addSuffix: true })}
                </div>
            </MemoizedClickable>
            <FileDropdownMenu file={file} />
        </div>
    );
}

export default memo(FileObjectRow, (prevProps, nextProps) => {
    /* eslint-disable @typescript-eslint/no-unused-vars */
    const { isArchiveType, isEditable, ...prevFile } = prevProps.file;
    const { isArchiveType: nextIsArchiveType, isEditable: nextIsEditable, ...nextFile } = nextProps.file;
    /* eslint-enable @typescript-eslint/no-unused-vars */

    return isEqual(prevFile, nextFile);
});
