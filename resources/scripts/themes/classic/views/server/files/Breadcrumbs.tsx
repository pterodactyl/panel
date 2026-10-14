import { Fragment, type ReactNode } from 'react';
import { PanelLink } from '@/sdk';
import type { FileBreadcrumb } from '@/sdk/theme';

const linkClass = 'px-1 text-foreground no-underline transition-colors duration-150 hover:text-accent';

interface Props {
    crumbs: readonly FileBreadcrumb[];
    /** The open file, after the crumbs. */
    file?: string | null;
    /** Shown before the path, in place of its indent. */
    children?: ReactNode;
}

export default function Breadcrumbs({ crumbs, file, children }: Props) {
    return (
        <div className='flex grow-0 items-center text-sm text-muted-foreground overflow-x-hidden'>
            {children || <div className='w-12' />}/<span className='px-1 text-muted-foreground'>home</span>/
            {crumbs.map(({ name, path, destination }) =>
                destination ? (
                    <Fragment key={path}>
                        <PanelLink destination={destination} className={linkClass}>
                            {name}
                        </PanelLink>
                        /
                    </Fragment>
                ) : (
                    <span key={path} className='px-1 text-muted-foreground'>
                        {name}
                    </span>
                )
            )}
            {file && <span className='px-1 text-muted-foreground'>{file}</span>}
        </div>
    );
}
