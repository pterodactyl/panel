import React, { createContext, useContext, useMemo } from 'react';
import { createPortal } from 'react-dom';

/** Where overlays render instead of the page's own portal; a workbench preview sets the body of its frame. */
export const PortalContainerContext = createContext<HTMLElement | null>(null);

/** The element overlays portal into, or undefined for the default (the document body). */
export function usePortalContainer(): HTMLElement | undefined {
    return useContext(PortalContainerContext) ?? undefined;
}

/** The document the content renders into: a preview frame's, or the page's. */
export function useOwnerDocument(): Document {
    return usePortalContainer()?.ownerDocument ?? document;
}

export interface ViewDocument {
    readonly document: Document;
    readonly window: Window;
}

/**
 * The document and window a view renders into: a workbench preview frame's, or the page's. Attach
 * listeners such as a keydown for Escape here, so they hear the preview as they hear the page.
 */
export function useViewDocument(): ViewDocument {
    const owner = useOwnerDocument();

    return useMemo(() => ({ document: owner, window: owner.defaultView ?? window }), [owner]);
}

/** Renders `children` into the overlay layer of the view's document. */
export default function Portal({ children }: { children: React.ReactNode }) {
    const container = usePortalContainer();

    return createPortal(children, container ?? document.getElementById('modal-portal') ?? document.body);
}
