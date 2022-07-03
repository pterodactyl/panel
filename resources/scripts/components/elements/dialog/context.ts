import React from 'react';
import { DialogContextType, DialogWrapperContextType } from './types';

export const DialogContext = React.createContext<DialogContextType>({
    setIcon: () => null,
    setFooter: () => null,
    setIconPosition: () => null,
});

export const DialogWrapperContext = React.createContext<DialogWrapperContextType>({
    props: {},
    setProps: () => null,
    close: () => null,
});
