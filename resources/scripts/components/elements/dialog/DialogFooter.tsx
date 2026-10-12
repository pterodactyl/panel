import React, { use } from 'react';
import { useSkin } from '@/themes/skins';
import { DialogContext } from './context';

const DialogFooter = ({ children }: { children: React.ReactNode }) => {
    const ui = useSkin('Dialog', { described: use(DialogContext).described });

    return <div {...ui('footer')}>{children}</div>;
};

export default DialogFooter;
