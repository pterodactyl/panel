import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { NavLink, useParams } from 'react-router-dom';

interface Props {
    withinFileEditor?: boolean;
    isNewFile?: boolean;
}

export default ({ withinFileEditor, isNewFile }: Props) => {
    const { action } = useParams();
    const [ file, setFile ] = useState<string | null>(null);
    const id = ServerContext.useStoreState(state => state.server.data!.id);
    const directory = ServerContext.useStoreState(state => state.files.directory);
    const setDirectory = ServerContext.useStoreActions(actions => actions.files.setDirectory);

    useEffect(() => {
        const parts = window.location.hash.replace(/^#(\/)*/, '/').split('/');

        if (withinFileEditor && !isNewFile) {
            setFile(parts.pop() || null);
        }

        setDirectory(parts.join('/'));
    }, [ withinFileEditor, isNewFile, setDirectory ]);

    const breadcrumbs = (): { name: string; path?: string }[] => directory.split('/')
        .filter(directory => !!directory)
        .map((directory, index, dirs) => {
            if (!withinFileEditor && index === dirs.length - 1) {
                return { name: directory };
            }

            return { name: directory, path: `/${dirs.slice(0, index + 1).join('/')}` };
        });

    return (
        <div className={'flex items-center text-sm mb-4 text-neutral-500'}>
            /<span className={'px-1 text-neutral-300'}>home</span>/
            <NavLink
                to={`/server/${id}/files`}
                onClick={() => setDirectory('/')}
                className={'px-1 text-neutral-200 no-underline hover:text-neutral-100'}
            >
                container
            </NavLink>/
            {
                breadcrumbs().map((crumb, index) => (
                    crumb.path ?
                        <React.Fragment key={index}>
                            <NavLink
                                to={`/server/${id}/files#${crumb.path}`}
                                onClick={() => setDirectory(crumb.path!)}
                                className={'px-1 text-neutral-200 no-underline hover:text-neutral-100'}
                            >
                                {crumb.name}
                            </NavLink>/
                        </React.Fragment>
                        :
                        <span key={index} className={'px-1 text-neutral-300'}>{crumb.name}</span>
                ))
            }
            {file &&
            <React.Fragment>
                <span className={'px-1 text-neutral-300'}>{file}</span>
            </React.Fragment>
            }
        </div>
    );
};
