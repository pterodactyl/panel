import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { NavLink } from 'react-router-dom';
import { cleanDirectoryPath } from '@/helpers';
import tw from 'twin.macro';

interface Props {
    withinFileEditor?: boolean;
    isNewFile?: boolean;
}

export default ({ withinFileEditor, isNewFile }: Props) => {
    const [ file, setFile ] = useState<string | null>(null);
    const id = ServerContext.useStoreState(state => state.server.data!.id);
    const directory = ServerContext.useStoreState(state => state.files.directory);

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
                return { name: decodeURIComponent(directory) };
            }

            return { name: decodeURIComponent(directory), path: `/${dirs.slice(0, index + 1).join('/')}` };
        });

    return (
        <div css={tw`flex items-center text-sm mb-4 text-neutral-500`}>
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
                <span css={tw`px-1 text-neutral-300`}>{decodeURIComponent(file)}</span>
            </React.Fragment>
            }
        </div>
    );
};
