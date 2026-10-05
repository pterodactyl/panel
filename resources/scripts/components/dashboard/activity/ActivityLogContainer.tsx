import { keepPreviousData } from '@tanstack/react-query';
import { useNavigate } from '@tanstack/react-router';
import { useActivityFacets, useActivityLogs } from '@/api/account/activity/queries';
import PageContentBlock from '@/components/elements/PageContentBlock';
import ActivityLogView, { type ActivityLogSearch } from '@/components/elements/activity/ActivityLogView';
import { useActivitySearch } from '@/router/search';

export default function ActivityLogContainer() {
    const navigate = useNavigate();
    const { page, event, user } = useActivitySearch();
    const facets = useActivityFacets();
    const { data, error, isFetching, refetch } = useActivityLogs(
        { page, filters: { event, user }, sorts: { timestamp: -1 } },
        { refetchOnWindowFocus: false, placeholderData: keepPreviousData }
    );

    return (
        <PageContentBlock title={'Account Activity Log'}>
            <ActivityLogView
                data={data}
                error={error}
                isFetching={isFetching}
                refetch={refetch}
                event={event}
                user={user}
                facets={facets}
                showUserFilter={false}
                emptyMessage={'Actions taken on your account will appear here.'}
                onNavigate={(search: ActivityLogSearch) =>
                    navigate({ to: '/account/activity', search, replace: true, viewTransition: false })
                }
            />
        </PageContentBlock>
    );
}
