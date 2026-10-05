import React from 'react';
import type { DialogContextType } from './types';

export const DialogContext = React.createContext<DialogContextType>({
    setIcon: () => null,
    setIconPosition: () => null,
});
