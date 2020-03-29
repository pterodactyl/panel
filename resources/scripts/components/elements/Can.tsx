import React from 'react';
import { usePermissions } from '@/plugins/usePermissions';

interface Props {
    action: string | string[];
    renderOnError?: React.ReactNode | null;
    children: React.ReactNode;
}

const Can = ({ action, renderOnError, children }: Props) => {
    const can = usePermissions(action);

    return (
        <>
            {can.every(p => p) ? children : renderOnError}
        </>
    );
};

export default Can;
