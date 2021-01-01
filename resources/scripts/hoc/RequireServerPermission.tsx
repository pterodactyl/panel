import React from 'react';
import Can from '@/components/elements/Can';
import ScreenBlock from '@/components/screens/ScreenBlock';
export interface RequireServerPermissionProps {
    permissions: string | string[]
}

const RequireServerPermission: React.FC<RequireServerPermissionProps> = ({ children, permissions }) => {
    return (
        <Can
            action={permissions}
            renderOnError={
                <ScreenBlock
                    image={'/assets/svgs/server_error.svg'}
                    title={'Access Denied'}
                    message={'You do not have permission to access this page.'}
                />
            }
        >
            {children}
        </Can>
    );
};

export default RequireServerPermission;
