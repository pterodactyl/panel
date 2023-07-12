import type { ReactNode } from 'react';

import Can from '@/components/elements/Can';
import { ServerError } from '@/components/elements/ScreenBlock';

export interface RequireServerPermissionProps {
    children?: ReactNode;

    permissions: string | string[];
}

function RequireServerPermission({ children, permissions }: RequireServerPermissionProps) {
    return (
        <Can
            action={permissions}
            renderOnError={
                <ServerError title={'Access Denied'} message={'You do not have permission to access this page.'} />
            }
        >
            {children}
        </Can>
    );
}

export default RequireServerPermission;
