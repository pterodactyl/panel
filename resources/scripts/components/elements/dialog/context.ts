import { createContext } from 'react';
import { DialogContextType, DialogWrapperContextType } from './types';

export const DialogContext = createContext<DialogContextType>({
    setIcon: () => null,
    setFooter: () => null,
    setIconPosition: () => null,
});

export const DialogWrapperContext = createContext<DialogWrapperContextType>({
    props: {},
    setProps: () => null,
    close: () => null,
});
