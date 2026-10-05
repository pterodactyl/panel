import { useCallback, useState } from 'react';

export const useDialogState = (initialOpen = false) => {
    const [open, setOpen] = useState(initialOpen);
    const show = useCallback(() => setOpen(true), []);
    const hide = useCallback(() => setOpen(false), []);
    const toggle = useCallback(() => setOpen((current) => !current), []);

    return { open, setOpen, show, hide, toggle };
};
