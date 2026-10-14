import React from 'react';
import { createPortal } from 'react-dom';

/** Renders `children` into the page's overlay layer, or the body where there is none, as in tests. */
export default function Portal({ children }: { children: React.ReactNode }) {
    return createPortal(children, document.getElementById('modal-portal') ?? document.body);
}
