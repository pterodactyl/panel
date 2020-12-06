import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { NavLink, useRouteMatch } from 'react-router-dom';
import { cleanDirectoryPath } from '@/helpers';
import tw from 'twin.macro';
import { FileActionCheckbox } from '@/components/server/files/SelectFileCheckbox';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';

interface Props {
    withinFileEditor?: boolean;
    isNewFile?: boolean;
}

export default ({ withinFileEditor, isNewFile }: Props) => {
    const [ file, setFile ] = useState<string | null>(null);
    const { params } = useRouteMatch<Record<string, string>>();
    const id = ServerContext.useStoreState(state => state.server.data!.id);
    const directory = ServerContext.useStoreState(state => state.files.directory);

    const { data: files } = useFileManagerSwr();
    const setSelectedFiles = ServerContext.useStoreActions(actions => actions.files.setSelectedFiles);
    const selectedFilesLength = ServerContext.useStoreState(state => state.files.selectedFiles.length);

    useEffect(() => {
        const parts = cleanDirectoryPath(window.location.hash).split('/');

        if (withinFileEditor && !isNewFile) {
            setFile(parts.pop() || null);
        }
    }, [ withinFileEditor, isNewFile ]);

    const breadcrumbs = (): { name: string; path?: string }[] => directory.split('/')
        .filter(directory => !!directory)
        .map((directory, index, dirs) => {
            if (!withinFileEditor && index === dirs.length - 1) {
                return { name: directory };
            }

            return { name: directory, path: `/${dirs.slice(0, index + 1).join('/')}` };
        });

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedFiles(e.currentTarget.checked ? (files?.map(file => file.name) || []) : []);
    };

    return (
        <div css={tw`flex flex-grow-0 items-center text-sm text-neutral-500 overflow-x-hidden`}>
            {(files && files.length > 0 && !params?.action) ?
                <FileActionCheckbox
                    type={'checkbox'}
                    css={tw`mx-4`}
                    checked={selectedFilesLength === (files ? files.length : -1)}
                    onChange={onSelectAllClick}
                />
                :
                <div css={tw`w-12`}/>
            }
            /<span css={tw`px-1 text-neutral-300`}>home</span>/
            <NavLink
                to={`/server/${id}/files`}
                css={tw`px-1 text-neutral-200 no-underline hover:text-neutral-100`}
            >
                container
            </NavLink>/
            {
                breadcrumbs().map((crumb, index) => (
                    crumb.path ?
                        <React.Fragment key={index}>
                            <NavLink
                                to={`/server/${id}/files#${crumb.path}`}
                                css={tw`px-1 text-neutral-200 no-underline hover:text-neutral-100`}
                            >
                                {crumb.name}
                            </NavLink>/
                        </React.Fragment>
                        :
                        <span key={index} css={tw`px-1 text-neutral-300`}>{crumb.name}</span>
                ))
            }
            {file &&
            <React.Fragment>
                <span css={tw`px-1 text-neutral-300`}>{decodeURI(file)}</span>
            </React.Fragment>
            }
        </div>
    );
};
