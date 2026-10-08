import { keepPreviousData } from '@tanstack/react-query';
import { useNavigate } from '@tanstack/react-router';
import { useAdminActivityFacets, useAdminActivityLogs } from '@/api/admin/activity/queries';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import ActivityLogView, { type ActivityLogSearch } from '@/components/elements/activity/ActivityLogView';
import { useActivitySearch } from '@/router/search';

export default function ActivityContainer() {
    const navigate = useNavigate();
    const { page, event, user } = useActivitySearch();
    const facets = useAdminActivityFacets();
    const { data, error, isFetching, refetch } = useAdminActivityLogs(
        { page, filters: { event, user }, sorts: { timestamp: -1 } },
        { refetchOnWindowFocus: false, placeholderData: keepPreviousData }
    );

    return (
        <AdminContentBlock
            title='Admin · Activity'
            heading='Activity'
            description='Actions taken through the admin panel, newest first. Server and account activity stays on those screens.'
        >
            <ActivityLogView
                data={data}
                error={error}
                isFetching={isFetching}
                refetch={refetch}
                event={event}
                user={user}
                facets={facets}
                emptyMessage='Actions taken through the admin panel will appear here.'
                onNavigate={(search: ActivityLogSearch) =>
                    navigate({ to: '/panel/activity', search, replace: true, viewTransition: false })
                }
            />
        </AdminContentBlock>
    );
}
