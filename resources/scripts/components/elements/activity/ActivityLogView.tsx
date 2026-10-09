import { History, ListFilter, Monitor, XCircle } from 'lucide-react';
import type { ActivityLog } from '@/api/activity';
import type { ActivityFacets } from '@/api/activityFacets';
import { httpErrorToHuman } from '@/api/http';
import ActivityLogEntry from '@/components/elements/activity/ActivityLogEntry';
import { Alert } from '@/components/elements/alert';
import { ButtonStyle } from '@/components/elements/Button';
import { NewButton } from '@/components/elements/NewButton';
import Spinner from '@/components/elements/Spinner';
import PaginationFooter from '@/components/elements/table/PaginationFooter';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import Select, { type SelectGroup, type SelectOption } from '@/components/ui/Select';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { isString } from '@/lib/objects';
import { getPageSearch } from '@/router/search';

export interface ActivityLogSearch {
    page?: number;
    event?: string;
    user?: string;
}

interface ActivityLogPage {
    data: ActivityLog[];
    meta: { pagination: { total: number; count: number; per_page: number; current_page: number; total_pages: number } };
}

interface Props {
    data: ActivityLogPage | undefined;
    error: Error | null;
    isFetching: boolean;
    refetch: () => void;
    event?: string;
    user?: string;
    emptyMessage: string;
    facets: ActivityFacets;
    showUserFilter?: boolean;
    onNavigate: (search: ActivityLogSearch) => void;
}

const ACRONYMS = new Map([
    ['api', 'API'],
    ['sftp', 'SFTP'],
    ['ip', 'IP'],
    ['2fa', '2FA'],
]);

const humanise = (value: string) =>
    value
        .split(/[-_]/)
        .map(
            (word, index) =>
                ACRONYMS.get(word.toLowerCase()) ?? (index === 0 ? word.charAt(0).toUpperCase() + word.slice(1) : word)
        )
        .join(' ');

const eventGroups = (events: string[]): SelectGroup[] => {
    const reset: SelectGroup = { label: '', options: [{ value: '', label: 'All events' }] };
    const grouped = new Map<string, SelectOption[]>();

    for (const event of events) {
        const [namespace = '', remainder = ''] = event.split(':');
        const separator = remainder.indexOf('.');
        const resource = separator === -1 ? namespace : remainder.slice(0, separator);
        const action = separator === -1 ? remainder : remainder.slice(separator + 1);

        const options = grouped.get(resource) ?? [];

        options.push({
            value: event,
            label: humanise(action),
            textLabel: `${humanise(resource)} · ${humanise(action)}`,
        });
        grouped.set(resource, options);
    }

    return [
        reset,
        ...[...grouped.entries()]
            .map(([resource, options]) => ({
                label: humanise(resource),
                options: options.sort((a, b) => String(a.label).localeCompare(String(b.label))),
            }))
            .sort((a, b) => a.label.localeCompare(b.label)),
    ];
};

interface ActivityLogResultsProps {
    data: ActivityLogPage | undefined;
    isFetching: boolean;
    hasFilters: boolean;
    emptyMessage: string;
    onClearFilters: () => void;
}

function ActivityLogResults({ data, isFetching, hasFilters, emptyMessage, onClearFilters }: ActivityLogResultsProps) {
    if (!data && isFetching) {
        return <Spinner centered />;
    }

    if (data?.data.length) {
        return (
            <div className='bg-card'>
                {data.data.map((activity) => (
                    <ActivityLogEntry key={activity.attributes.id} activity={activity}>
                        {isString(activity.attributes.properties.useragent) && (
                            <Tooltip content={activity.attributes.properties.useragent} placement='top'>
                                <span>
                                    <Monitor />
                                </span>
                            </Tooltip>
                        )}
                    </ActivityLogEntry>
                ))}
            </div>
        );
    }

    return (
        <Empty className='border bg-card'>
            <EmptyHeader>
                <EmptyMedia variant='icon'>{hasFilters ? <ListFilter /> : <History />}</EmptyMedia>
                <EmptyTitle>{hasFilters ? 'No matching activity' : 'No activity yet'}</EmptyTitle>
                <EmptyDescription>
                    {hasFilters ? 'No activity matches the selected filters.' : emptyMessage}
                </EmptyDescription>
            </EmptyHeader>
            {hasFilters && (
                <EmptyContent>
                    <NewButton isSecondary icon={XCircle} onClick={onClearFilters}>
                        Clear filters
                    </NewButton>
                </EmptyContent>
            )}
        </Empty>
    );
}

export default function ActivityLogView({
    data,
    error,
    isFetching,
    refetch,
    event,
    user,
    emptyMessage,
    facets,
    showUserFilter = true,
    onNavigate,
}: Props) {
    const hasFilters = Boolean(event) || Boolean(user);

    const eventOptions = eventGroups(facets.events);
    const userOptions = [
        { value: '', label: 'All users' },
        ...facets.users.map((option) => ({
            value: String(option.id),
            label: option.email ? `${option.username} (${option.email})` : option.username,
        })),
    ];

    const withFilter = (key: 'event' | 'user', value: string | null) => {
        const next: ActivityLogSearch = {};
        const current = { event, user };
        const merged = { ...current, [key]: value ?? undefined };

        if (merged.user) {
            next.user = merged.user;
        }

        if (merged.event) {
            next.event = merged.event;
        }

        onNavigate(next);
    };

    return (
        <>
            {error && !isFetching && (
                <div className='mb-4 space-y-3'>
                    <Alert type='danger'>{httpErrorToHuman(error)}</Alert>
                    <ButtonStyle type='button' color='grey' onClick={() => refetch()}>
                        Retry
                    </ButtonStyle>
                </div>
            )}
            <div className='mb-3 flex flex-col gap-2 sm:flex-row sm:items-stretch'>
                {showUserFilter && (
                    <div className='w-full sm:max-w-xs'>
                        <Select
                            options={userOptions}
                            value={user ?? ''}
                            placeholder='All users'
                            searchPlaceholder='Search users…'
                            emptyMessage='No users have acted here yet.'
                            onChange={(value) => withFilter('user', String(value) || null)}
                        />
                    </div>
                )}
                <div className='w-full sm:max-w-sm'>
                    <Select
                        groups={eventOptions}
                        value={event ?? ''}
                        placeholder='All events'
                        searchPlaceholder='Search events…'
                        emptyMessage='Nothing has been recorded here yet.'
                        onChange={(value) => withFilter('event', String(value) || null)}
                    />
                </div>
                {hasFilters && (
                    <ButtonStyle
                        type='button'
                        color='grey'
                        className='inline-flex items-center justify-center w-full sm:w-auto sm:shrink-0 h-auto'
                        onClick={() => onNavigate({})}
                    >
                        Clear Filters <XCircle className='w-4 h-4 ml-2' />
                    </ButtonStyle>
                )}
            </div>
            <ActivityLogResults
                data={data}
                isFetching={isFetching}
                hasFilters={hasFilters}
                emptyMessage={emptyMessage}
                onClearFilters={() => onNavigate({})}
            />
            {data && (
                <PaginationFooter
                    pagination={data.meta.pagination}
                    onPageSelect={(page) => {
                        const search: ActivityLogSearch = getPageSearch(page);

                        if (event) {
                            search.event = event;
                        }

                        if (user) {
                            search.user = user;
                        }

                        onNavigate(search);
                    }}
                />
            )}
        </>
    );
}
