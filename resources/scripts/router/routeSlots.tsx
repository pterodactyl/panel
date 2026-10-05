import { type FunctionComponent } from 'react';
import { lazyRouteComponent, useLocation, useParams } from '@tanstack/react-router';
import Slot from '@/extensions/Slot';
import type { RouteSlotData, RouteSlotName } from '@/extensions/registry';
import Spinner from '@/components/elements/Spinner';
import { routeParamStrings } from '@/router/params';

type Importer = () => Promise<{ default: FunctionComponent }>;

export interface RouteSlotPair {
    before: RouteSlotName;
    after: RouteSlotName;
}

export const useRouteSlotData = (): RouteSlotData => {
    const location = useLocation();
    const params = useParams({ strict: false });

    return {
        pathname: location.pathname,
        search: location.search,
        params: routeParamStrings(params),
    };
};

export const slottedRouteComponent = (factory: Importer, slots: RouteSlotPair) => {
    const Screen = lazyRouteComponent(factory);

    function SlottedRouteComponent() {
        const data = useRouteSlotData();

        return (
            <>
                <Slot name={slots.before} data={data} />
                <Spinner.Suspense>
                    <Screen />
                </Spinner.Suspense>
                <Slot name={slots.after} data={data} />
            </>
        );
    }

    SlottedRouteComponent.preload = Screen.preload;

    return SlottedRouteComponent;
};
