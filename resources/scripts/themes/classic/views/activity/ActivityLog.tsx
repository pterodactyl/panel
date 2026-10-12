import { History, ListFilter, XCircle } from 'lucide-react';
import {
    Alert,
    ButtonStyle,
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    NewButton,
    PaginationFooter,
    Select,
    Spinner,
    type ActivityLogModel,
    type ActivityScope,
} from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import ActivityLogEntry from './ActivityLogEntry';

const emptyMessages = {
    account: 'Actions taken on your account will appear here.',
    server: 'Actions taken on this server will appear here.',
    admin: 'Actions taken through the admin panel will appear here.',
} satisfies Record<ActivityScope, string>;

function Results({ model, filtered }: { model: ActivityLogModel; filtered: boolean }) {
    if (model.loading) {
        return <Spinner centered />;
    }

    if (model.entries.length) {
        return (
            <div className='bg-card'>
                {model.entries.map((entry) => (
                    <ActivityLogEntry key={entry.id} entry={entry} />
                ))}
            </div>
        );
    }

    return (
        <Empty className='border bg-card'>
            <EmptyHeader>
                <EmptyMedia variant='icon'>{filtered ? <ListFilter /> : <History />}</EmptyMedia>
                <EmptyTitle>{filtered ? 'No matching activity' : 'No activity yet'}</EmptyTitle>
                <EmptyDescription>
                    {filtered ? 'No activity matches the selected filters.' : emptyMessages[model.scope]}
                </EmptyDescription>
            </EmptyHeader>
            {filtered && (
                <EmptyContent>
                    <NewButton isSecondary icon={XCircle} onClick={model.actions.clearFilters}>
                        Clear filters
                    </NewButton>
                </EmptyContent>
            )}
        </Empty>
    );
}

function Filters({ model, filtered }: { model: ActivityLogModel; filtered: boolean }) {
    const { actions } = model;

    return (
        <div className='mb-3 flex flex-col gap-2 sm:flex-row sm:items-stretch'>
            {model.users && (
                <div className='w-full sm:max-w-xs'>
                    <Select
                        options={[{ value: '', label: 'All users' }, ...model.users]}
                        value={model.user ?? ''}
                        placeholder='All users'
                        searchPlaceholder='Search users…'
                        emptyMessage='No users have acted here yet.'
                        onChange={(value) => actions.setUser(String(value) || null)}
                    />
                </div>
            )}
            <div className='w-full sm:max-w-sm'>
                <Select
                    groups={[
                        { label: '', options: [{ value: '', label: 'All events' }] },
                        ...model.events.map((group) => ({ label: group.label, options: [...group.options] })),
                    ]}
                    value={model.event ?? ''}
                    placeholder='All events'
                    searchPlaceholder='Search events…'
                    emptyMessage='Nothing has been recorded here yet.'
                    onChange={(value) => actions.setEvent(String(value) || null)}
                />
            </div>
            {filtered && (
                <ButtonStyle
                    type='button'
                    color='grey'
                    className='inline-flex items-center justify-center w-full sm:w-auto sm:shrink-0 h-auto'
                    onClick={actions.clearFilters}
                >
                    Clear Filters <XCircle className='w-4 h-4 ml-2' />
                </ButtonStyle>
            )}
        </div>
    );
}

export default function ActivityLog({ model }: ViewProps<'activity.log'>) {
    const filtered = model.event !== null || model.user !== null;

    return (
        <>
            {model.error !== null && (
                <div className='mb-4 space-y-3'>
                    <Alert type='danger'>{model.error}</Alert>
                    <ButtonStyle type='button' color='grey' onClick={model.retry}>
                        Retry
                    </ButtonStyle>
                </div>
            )}
            <Filters model={model} filtered={filtered} />
            <Results model={model} filtered={filtered} />
            {model.pagination && (
                <PaginationFooter pagination={model.pagination} onPageSelect={model.actions.setPage} />
            )}
        </>
    );
}
