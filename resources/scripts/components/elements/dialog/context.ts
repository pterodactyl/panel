import React from 'react';

interface DialogContextType {
    icon: React.RefObject<HTMLDivElement | undefined>;
    buttons: React.RefObject<HTMLDivElement | undefined>;
}

const DialogContext = React.createContext<DialogContextType>({
    icon: React.createRef(),
    buttons: React.createRef(),
});

export default DialogContext;
