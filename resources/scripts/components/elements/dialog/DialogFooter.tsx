import { useContext } from 'react';
import * as React from 'react';
import { DialogContext } from './';
import { useDeepCompareEffect } from '@/plugins/useDeepCompareEffect';

export default ({ children }: { children: React.ReactNode }) => {
    const { setFooter } = useContext(DialogContext);

    useDeepCompareEffect(() => {
        setFooter(
            <div className={'flex items-center justify-end space-x-3 rounded-b bg-slate-700 px-6 py-3'}>
                {children}
            </div>,
        );
    }, [children]);

    return null;
};
