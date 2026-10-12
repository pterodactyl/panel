import React from 'react';
import { PanelLink, type PanelDestination } from '@/sdk/navigation';
import { replaceable, useSkin } from '@/themes/skins';

export interface ListRowProps extends React.HTMLAttributes<HTMLElement> {
    as?: 'div' | 'li' | 'article';
    /** Renders the row as a link to the destination. */
    destination?: PanelDestination;
    exact?: boolean;
    /** Hover and press feedback; on for links and clickable rows unless set. */
    interactive?: boolean;
}

/** A row of a list, such as a server on the dashboard. */
function CoreListRow({ as: Element = 'div', destination, exact, interactive, className, ...props }: ListRowProps) {
    const ui = useSkin('ListRow', {
        interactive: interactive ?? (destination !== undefined || props.onClick !== undefined),
    });

    if (destination) {
        return <PanelLink destination={destination} exact={exact} {...ui('root', className)} {...props} />;
    }

    return <Element {...ui('root', className)} {...props} />;
}

const ListRow = replaceable('ListRow', CoreListRow);

export default ListRow;
