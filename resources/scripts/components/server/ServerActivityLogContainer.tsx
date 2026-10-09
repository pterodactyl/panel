import { keepPreviousData } from '@tanstack/react-query';
import { useNavigate, useParams } from '@tanstack/react-router';
import { useActivityFacets, useActivityLogs } from '@/api/server/activity/queries';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import ActivityLogView, { type ActivityLogSearch } from '@/components/elements/activity/ActivityLogView';
import { useActivitySearch } from '@/router/search';

export default function ServerActivityLogContainer() {
    const navigate = useNavigate();
    const { id } = useParams({ from: '/authenticated/server/$id' });
    const { page, event, user } = useActivitySearch();
    const facets = useActivityFacets();
    const { data, error, isFetching, refetch } = useActivityLogs(
        { page, filters: { event, user }, sorts: { timestamp: -1 } },
        { refetchOnWindowFocus: false, placeholderData: keepPreviousData }
    );

    return (
        <ServerContentBlock title='Activity Log'>
            <ActivityLogView
                data={data}
                error={error}
                isFetching={isFetching}
                refetch={refetch}
                event={event}
                user={user}
                facets={facets}
                emptyMessage='Actions taken on this server will appear here.'
                onNavigate={(search: ActivityLogSearch) =>
                    navigate({
                        to: '/server/$id/activity',
                        params: { id },
                        search,
                        replace: true,
                        viewTransition: false,
                    })
                }
            />
        </ServerContentBlock>
    );
}
