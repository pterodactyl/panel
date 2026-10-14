import type React from 'react';
import { replaceable, useSkin } from '@/themes/skins';

export interface PageHeadingProps {
    title: React.ReactNode;
    description?: React.ReactNode;
    actions?: React.ReactNode;
    className?: string;
}

function CorePageHeading({ title, description, actions, className }: PageHeadingProps) {
    const ui = useSkin('PageHeading', {});

    return (
        <header {...ui('root', className)}>
            <div {...ui('text')}>
                <h1 {...ui('title')}>{title}</h1>
                {description && <p {...ui('description')}>{description}</p>}
            </div>
            {actions && <div {...ui('actions')}>{actions}</div>}
        </header>
    );
}

const PageHeading = replaceable('PageHeading', CorePageHeading);

export default PageHeading;
