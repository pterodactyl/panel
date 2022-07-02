import React from 'react';
import { IconPosition } from './DialogIcon';

type Callback<T> = ((value: T) => void) | React.Dispatch<React.SetStateAction<T>>;

interface DialogContextType {
    setIcon: Callback<React.ReactNode>;
    setFooter: Callback<React.ReactNode>;
    setIconPosition: Callback<IconPosition>;
}

const DialogContext = React.createContext<DialogContextType>({
    setIcon: () => null,
    setFooter: () => null,
    setIconPosition: () => null,
});

export default DialogContext;
