import React, { createContext, useContext } from 'react';
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

export default function Portal({ children }: { children: React.ReactNode }) {
    const container = usePortalContainer();

    return createPortal(children, container ?? document.getElementById('modal-portal')!);
}
