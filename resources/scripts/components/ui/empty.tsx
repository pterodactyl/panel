import * as React from 'react';
import { replaceable, useSkin } from '@/themes/skins';

export type EmptySize = 'default' | 'compact';
export type EmptyMediaVariant = 'default' | 'icon';

export type EmptyProps = React.ComponentProps<'div'> & {
    /** `compact` tightens the spacing for an `Empty` inside a card, table or dialog. */
    size?: EmptySize;
};

const EmptySizeContext = React.createContext<EmptySize>('default');

const usePartSkin = () => useSkin('Empty', { size: React.use(EmptySizeContext) });

function CoreEmpty({ className, size = 'default', ...props }: EmptyProps) {
    const ui = useSkin('Empty', { size });

    return (
        <EmptySizeContext.Provider value={size}>
            <div {...ui('root', className)} {...props} />
        </EmptySizeContext.Provider>
    );
}

const Empty = replaceable('Empty', CoreEmpty);

function EmptyHeader({ className, ...props }: React.ComponentProps<'div'>) {
    return <div {...usePartSkin()('header', className)} {...props} />;
}

/** `icon` frames a single icon in a tile; `default` leaves the media unframed. */
function EmptyMedia({
    className,
    variant = 'default',
    ...props
}: React.ComponentProps<'div'> & { variant?: EmptyMediaVariant }) {
    return <div {...usePartSkin()('media', className, { variant })} {...props} />;
}

function EmptyTitle({ className, ...props }: React.ComponentProps<'div'>) {
    return <div {...usePartSkin()('title', className)} {...props} />;
}

function EmptyDescription({ className, ...props }: React.ComponentProps<'div'>) {
    return <div {...usePartSkin()('description', className)} {...props} />;
}

function EmptyContent({ className, ...props }: React.ComponentProps<'div'>) {
    return <div {...usePartSkin()('content', className)} {...props} />;
}

export { Empty, EmptyHeader, EmptyMedia, EmptyTitle, EmptyDescription, EmptyContent };
