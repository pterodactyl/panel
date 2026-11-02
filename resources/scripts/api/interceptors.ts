import http from '@/api/http';
import type { AxiosError } from 'axios';
import { endBootstrapSession, hasBootstrapSession } from '@/bootstrap';
import { router } from '@/router/router';
import { parseRedirectSearch } from '@/router/redirect';
import { isObject } from '@/lib/objects';

export const setupInterceptors = () => {
    http.interceptors.response.use(
        (resp) => resp,
        (error: AxiosError) => {
            if (error.response?.status === 401 && hasBootstrapSession()) {
                endBootstrapSession();
                void router.navigate({
                    to: '/auth/login',
                    search: parseRedirectSearch({ redirect: router.latestLocation.href }),
                    replace: true,
                });
            }

            if (error.response?.status === 400) {
                const data = error.response.data;
                const firstError =
                    isObject(data) && 'errors' in data && Array.isArray(data.errors) ? data.errors[0] : null;

                if (
                    isObject(firstError) &&
                    'code' in firstError &&
                    firstError.code === 'TwoFactorAuthRequiredException'
                ) {
                    if (!window.location.pathname.startsWith('/account')) {
                        void router.navigate({ to: '/account', replace: true, state: { twoFactorRedirect: true } });
                    }
                }
            }

            throw error;
        }
    );
};
