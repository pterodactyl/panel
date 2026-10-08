import React from 'react';
import { Link } from '@tanstack/react-router';
import { useCurrentServerIdentifier } from '@/api/server/queries';
import { useServerDirectory } from '@/state/server';
import { encodePathSegments } from '@/helpers';
import { dirname } from 'pathe';

interface Props {
    children?: React.ReactNode;
    withinFileEditor?: boolean;
    isNewFile?: boolean;
}

export default function FileManagerBreadcrumbs({ children, withinFileEditor, isNewFile }: Props) {
    const id = useCurrentServerIdentifier()!;
    const directory = useServerDirectory();
    const file = withinFileEditor && !isNewFile ? directory.split('/').pop() || null : null;
    const breadcrumbDirectory: string = file ? dirname(directory) : directory;

    const breadcrumbs = (): { name: string; path?: string }[] =>
        breadcrumbDirectory
            .split('/')
            .filter((directory) => !!directory)
            .map((directory, index, dirs) => {
                if (!withinFileEditor && index === dirs.length - 1) {
                    return { name: directory };
                }

                return { name: directory, path: `/${dirs.slice(0, index + 1).join('/')}` };
            });

    return (
        <div className='flex grow-0 items-center text-sm text-muted-foreground overflow-x-hidden'>
            {children || <div className='w-12' />}/<span className='px-1 text-muted-foreground'>home</span>/
            <Link
                to='/server/$id/files'
                params={{ id }}
                hash='/'
                className='px-1 text-foreground no-underline transition-colors duration-150 hover:text-accent'
            >
                container
            </Link>
            /
            {breadcrumbs().map((crumb) =>
                crumb.path ? (
                    <React.Fragment key={crumb.path}>
                        <Link
                            to='/server/$id/files'
                            params={{ id }}
                            hash={encodePathSegments(crumb.path)}
                            className='px-1 text-foreground no-underline transition-colors duration-150 hover:text-accent'
                        >
                            {crumb.name}
                        </Link>
                        /
                    </React.Fragment>
                ) : (
                    <span key={`current:${crumb.name}`} className='px-1 text-muted-foreground'>
                        {crumb.name}
                    </span>
                )
            )}
            {file && <span className='px-1 text-muted-foreground'>{file}</span>}
        </div>
    );
}
