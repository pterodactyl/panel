import type { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';

import { useStoreState } from '@/state/hooks';

function AuthenticatedRoute({ children }: { children?: ReactNode }): JSX.Element {
    const isAuthenticated = useStoreState(state => !!state.user.data?.uuid);

    const location = useLocation();

    if (isAuthenticated) {
        return <>{children}</>;
    }

    return <Navigate to="/auth/login" state={{ from: location.pathname }} />;
}

export default AuthenticatedRoute;
