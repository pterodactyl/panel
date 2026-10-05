import type React from 'react';
import { createPortal } from 'react-dom';

export default function Portal({ children }: { children: React.ReactNode }) {
    return createPortal(children, document.getElementById('modal-portal')!);
}
