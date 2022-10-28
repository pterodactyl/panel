import type { ReactNode } from 'react';
import { Route } from 'react-router-dom';

import Can from '@/components/elements/Can';
import { ServerError } from '@/components/elements/ScreenBlock';

interface Props {
    children?: ReactNode;

    path: string;
    permission: string | string[] | null;
}

function PermissionRoute({ permission, children, ...props }: Props) {
    return (
        <Route {...props}>
            {!permission ? (
                children
            ) : (
                <Can
                    matchAny
                    action={permission}
                    renderOnError={
                        <ServerError title="Access Denied" message="You do not have permission to access this page." />
                    }
                >
                    {children}
                </Can>
            )}
        </Route>
    );
}

export default PermissionRoute;
