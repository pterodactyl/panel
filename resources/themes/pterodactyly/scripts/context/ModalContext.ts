import React from 'react';

export interface ModalContextValues {
    dismiss: () => void;
    toggleSpinner: (visible?: boolean) => void;
}

const ModalContext = React.createContext<ModalContextValues>({
    dismiss: () => null,
    toggleSpinner: () => null,
});

ModalContext.displayName = 'ModalContext';

export default ModalContext;
