import React from 'react';
import { SettableModalProps } from '@/hoc/asModal';

export interface ModalContextValues {
    dismiss: () => void;
    setPropOverrides: (
        value:
            | ((current: Readonly<Partial<SettableModalProps>>) => Partial<SettableModalProps>)
            | Partial<SettableModalProps>
            | null
    ) => void;
}

const ModalContext = React.createContext<ModalContextValues>({
    dismiss: () => null,
    setPropOverrides: () => null,
});

ModalContext.displayName = 'ModalContext';

export default ModalContext;
