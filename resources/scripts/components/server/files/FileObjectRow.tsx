import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFileImport } from '@fortawesome/free-solid-svg-icons/faFileImport';
import { faFileAlt } from '@fortawesome/free-solid-svg-icons/faFileAlt';
import { faFolder } from '@fortawesome/free-solid-svg-icons/faFolder';
import { bytesToHuman } from '@/helpers';
import differenceInHours from 'date-fns/difference_in_hours';
import format from 'date-fns/format';
import distanceInWordsToNow from 'date-fns/distance_in_words_to_now';
import React from 'react';
import { FileObject } from '@/api/server/files/loadDirectory';
import FileDropdownMenu from '@/components/server/files/FileDropdownMenu';

export default ({ file, directory }: { file: FileObject; directory: string }) => {
    return (
        <a
            key={file.name}
            href={file.isFile ? undefined : `#${directory}/${file.name}`}
            className={`
                flex bg-neutral-700 text-neutral-300 rounded-sm mb-px text-sm
                hover:text-neutral-100 cursor-pointer items-center no-underline hover:bg-neutral-600
            `}
            onClick={(e) => {
                if (file.isFile) {
                    return e.preventDefault();
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
                    distanceInWordsToNow(file.modifiedAt, { includeSeconds: true })
                }
            </div>
            <FileDropdownMenu file={file}/>
        </a>
    );
};
