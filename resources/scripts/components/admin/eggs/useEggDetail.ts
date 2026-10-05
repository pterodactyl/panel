import { useSuspenseQuery } from '@tanstack/react-query';
import { getRouteApi } from '@tanstack/react-router';
import { type AdminEgg, adminEggQueryOptions } from '@/api/admin/eggs/queries';

const eggDetailRoute = getRouteApi('/authenticated/panel/eggs/$eggId');

export const useEggDetail = (): AdminEgg => {
    const { eggId } = eggDetailRoute.useParams();

    return useSuspenseQuery(adminEggQueryOptions(eggId)).data;
};
