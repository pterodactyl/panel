import { use, useEffect } from 'react';
import { DialogContext } from './context';
import type { DialogIconProps } from './types';

/** Places a type icon beside the dialog title, or beside the whole content with `position="container"`. */
const DialogIcon = ({ type, position, className }: DialogIconProps) => {
    const { setIcon, setIconPosition } = use(DialogContext);

    useEffect(() => {
        setIcon({ type, className });

        return () => setIcon(undefined);
    }, [className, setIcon, type]);

    useEffect(() => {
        setIconPosition(position);

        return () => setIconPosition('title');
    }, [position, setIconPosition]);

    return null;
};

export default DialogIcon;
