import { useCallback, useEffect } from 'react';
import axios from 'axios';
import { useQueryErrorResetBoundary } from '@tanstack/react-query';
import { useRouter, type ErrorComponentProps } from '@tanstack/react-router';
import { httpErrorToHuman } from '@/api/http';
import { endBootstrapSession } from '@/bootstrap';
import { AccessDenied, NotFound, ServerError } from '@/components/elements/ScreenBlock';
import { parseRedirectSearch } from '@/router/redirect';

const accessDeniedMessage = 'You do not have permission to access this page.';

export class RouteAccessDenied extends Error {
    constructor(message = accessDeniedMessage) {
        super(message);
        this.name = 'RouteAccessDenied';
    }
}

const routeErrorStatus = (error: Error): number | undefined => {
    if (error instanceof RouteAccessDenied) return 403;

    return axios.isAxiosError(error) ? error.response?.status : undefined;
};

function SignInRedirect() {
    const router = useRouter();

    useEffect(() => {
        endBootstrapSession();
        void router.navigate({
            to: '/auth/login',
            search: parseRedirectSearch({ redirect: router.latestLocation.href }),
            replace: true,
        });
    }, [router]);

    return null;
}

export default function RouteError({ error }: ErrorComponentProps) {
    const router = useRouter();
    const queryErrorResetBoundary = useQueryErrorResetBoundary();
    const retry = useCallback(() => void router.invalidate(), [router]);

    useEffect(() => {
        queryErrorResetBoundary.reset();
    }, [queryErrorResetBoundary]);

    switch (routeErrorStatus(error)) {
        case 401:
            return <SignInRedirect />;
        case 403:
            return <AccessDenied message={error instanceof RouteAccessDenied ? error.message : accessDeniedMessage} />;
        case 404:
            return <NotFound />;
        default:
            return <ServerError message={httpErrorToHuman(error)} onRetry={retry} />;
    }
}
