import React, { createContext, useContext, useMemo } from 'react';
import { PanelLink, type PanelDestination } from '@/sdk/navigation';
import { replaceable, useSkin, type PrimitiveState, type UiTone } from '@/themes/skins';

export interface ListRowProps extends React.HTMLAttributes<HTMLElement> {
    as?: 'div' | 'li' | 'article';
    /** Renders the row as a link to the destination. */
    destination?: PanelDestination;
    exact?: boolean;
    /** Hover and press feedback; on for links and clickable rows unless set. */
    interactive?: boolean;
    /** Draws the `status` part in this tone, such as a server's power state. */
    status?: UiTone;
}

const RowState = createContext<PrimitiveState<'ListRow'>>({ interactive: false });

/** A row of a list, such as a server on the dashboard. */
function CoreListRow({
    as: Element = 'div',
    destination,
    exact,
    interactive,
    status,
    className,
    children,
    ...props
}: ListRowProps) {
    const clickable = interactive ?? (destination !== undefined || props.onClick !== undefined);
    const state = useMemo(() => ({ interactive: clickable, status }), [clickable, status]);
    const ui = useSkin('ListRow', state);
    const content = (
        <RowState.Provider value={state}>
            {children}
            {status && <div {...ui('status')} />}
        </RowState.Provider>
    );

    if (destination) {
        return (
            <PanelLink destination={destination} exact={exact} {...ui('root', className)} {...props}>
                {content}
            </PanelLink>
        );
    }

    return (
        <Element {...ui('root', className)} {...props}>
            {content}
        </Element>
    );
}

/** The `icon` part of the row around it, for an icon its content places. */
function ListRowIcon({ className, children }: { className?: string; children?: React.ReactNode }) {
    const ui = useSkin('ListRow', useContext(RowState));

    return <div {...ui('icon', className)}>{children}</div>;
}

const ListRow = Object.assign(replaceable('ListRow', CoreListRow), { Icon: ListRowIcon });

export default ListRow;
