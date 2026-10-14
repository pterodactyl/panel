import { useLocation, useParams } from '@tanstack/react-router';
import type { RouteSlotData } from '@/extensions/registry';
import { routeParamStrings } from '@/router/params';

export const useRouteSlotData = (): RouteSlotData => {
    const location = useLocation();
    const params = useParams({ strict: false });

    return {
        pathname: location.pathname,
        search: location.search,
        params: routeParamStrings(params),
    };
};
