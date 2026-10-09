import React from 'react';
import { usePermissions } from '@/plugins/usePermissions';

interface Props {
    action: string | string[];
    matchAny?: boolean;
    renderOnError?: React.ReactNode | null;
    children: React.ReactNode;
}

export default function Can({ action, matchAny = false, renderOnError, children }: Props) {
    const can = usePermissions(action);

    return (matchAny && can.some((p) => p)) || (!matchAny && can.every((p) => p)) ? children : renderOnError;
}
