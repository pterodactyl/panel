import React, { useContext } from 'react';
import { createPortal } from 'react-dom';
import DialogContext from '@/components/elements/dialog/context';

export default ({ children }: { children: React.ReactNode }) => {
    const { buttons } = useContext(DialogContext);

    if (!buttons.current) {
        return null;
    }

    const element = (
        <div className={'px-6 py-3 bg-gray-700 flex items-center justify-end space-x-3 rounded-b'}>{children}</div>
    );

    return createPortal(element, buttons.current);
};
